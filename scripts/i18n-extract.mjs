#!/usr/bin/env node
import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";
import { parse } from "@babel/parser";
import generateModule from "@babel/generator";
import traverseModule from "@babel/traverse";
import * as bt from "@babel/types";

const traverse = traverseModule.default || traverseModule;
const generate = generateModule.default || generateModule;
const rootDir = path.resolve(
    path.dirname(fileURLToPath(import.meta.url)),
    "..",
);

const defaultConfig = {
    src: "resources/js",
    out: "resources/js/locales/en/common.json",
    namespace: "common",
    write: false,
    files: [],
};

const translatableAttributes = new Set([
    "aria-label",
    "alt",
    "placeholder",
    "title",
]);
const ignoredDirectories = new Set([
    "node_modules",
    "vendor",
    "public",
    "storage",
    "locales",
    "i18n",
]);
const ignoredTexts = new Set(["BDGigs"]);

function parseArgs(argv) {
    const config = { ...defaultConfig };

    for (let index = 0; index < argv.length; index += 1) {
        const arg = argv[index];

        if (arg === "--write") {
            config.write = true;
        } else if (arg === "--src") {
            config.src = argv[index + 1] || config.src;
            index += 1;
        } else if (arg === "--out") {
            config.out = argv[index + 1] || config.out;
            index += 1;
        } else if (arg === "--namespace") {
            config.namespace = argv[index + 1] || config.namespace;
            index += 1;
        } else if (arg === "--file") {
            config.files.push(argv[index + 1]);
            index += 1;
        } else if (arg === "--help" || arg === "-h") {
            printHelp();
            process.exit(0);
        }
    }

    return config;
}

function printHelp() {
    console.log(`
Usage:
  npm run i18n:extract
  npm run i18n:extract -- --write
  npm run i18n:extract -- --file resources/js/components/layout/Header.jsx --write

Options:
  --src <path>        Source directory. Default: resources/js
  --out <path>        Locale JSON output. Default: resources/js/locales/en/common.json
  --file <path>       Transform a specific file. Can be repeated.
  --namespace <name>  Namespace label for logs. Default: common
  --write             Write JSX changes and locale JSON. Without this, dry-run only.
`);
}

function readJson(filePath) {
    if (!fs.existsSync(filePath)) return {};
    return JSON.parse(fs.readFileSync(filePath, "utf8"));
}

function writeJson(filePath, value) {
    fs.mkdirSync(path.dirname(filePath), { recursive: true });
    fs.writeFileSync(filePath, `${JSON.stringify(value, null, 2)}\n`);
}

function isSourceFile(filePath) {
    return (
        /\.(jsx|js)$/.test(filePath) &&
        !filePath.endsWith(".test.jsx") &&
        !filePath.endsWith(".test.js")
    );
}

function collectFiles(inputPath) {
    const absolutePath = path.resolve(rootDir, inputPath);
    if (!fs.existsSync(absolutePath)) return [];
    const stats = fs.statSync(absolutePath);

    if (stats.isFile()) {
        return isSourceFile(absolutePath) ? [absolutePath] : [];
    }

    const files = [];

    for (const entry of fs.readdirSync(absolutePath, { withFileTypes: true })) {
        if (entry.isDirectory()) {
            if (!ignoredDirectories.has(entry.name)) {
                files.push(
                    ...collectFiles(
                        path.relative(
                            rootDir,
                            path.join(absolutePath, entry.name),
                        ),
                    ),
                );
            }
            continue;
        }

        const filePath = path.join(absolutePath, entry.name);
        if (isSourceFile(filePath)) files.push(filePath);
    }

    return files;
}

function flattenJson(value, prefix = "", output = {}) {
    for (const [key, child] of Object.entries(value || {})) {
        const nextKey = prefix ? `${prefix}.${key}` : key;
        if (child && typeof child === "object" && !Array.isArray(child)) {
            flattenJson(child, nextKey, output);
        } else {
            output[nextKey] = child;
        }
    }

    return output;
}

function setDeepValue(target, key, value) {
    const parts = key.split(".");
    let cursor = target;

    parts.forEach((part, index) => {
        if (index === parts.length - 1) {
            cursor[part] = value;
            return;
        }

        cursor[part] = cursor[part] || {};
        cursor = cursor[part];
    });
}

function unflattenJson(flat) {
    const output = {};
    for (const key of Object.keys(flat).sort()) {
        setDeepValue(output, key, flat[key]);
    }
    return output;
}

function normalizeText(raw) {
    return String(raw || "")
        .replace(/\s+/g, " ")
        .trim();
}

function shouldTranslateText(text) {
    if (!text) return false;
    if (ignoredTexts.has(text)) return false;
    if (text.length < 2) return false;
    if (!/[\p{L}]/u.test(text)) return false;
    if (/^(true|false|null|undefined)$/i.test(text)) return false;
    if (/^(http|https|mailto|tel):/i.test(text)) return false;
    if (/\.(png|jpe?g|webp|gif|svg|pdf|zip)$/i.test(text)) return false;
    return true;
}

function toKeyPart(value) {
    const normalized = value
        .replace(/&/g, " and ")
        .replace(/['’]/g, "")
        .replace(/[^a-zA-Z0-9]+/g, " ")
        .trim()
        .split(/\s+/)
        .slice(0, 8);

    if (normalized.length === 0) return "text";

    return normalized
        .map((part, index) => {
            const lower = part.toLowerCase();
            return index === 0
                ? lower
                : lower.charAt(0).toUpperCase() + lower.slice(1);
        })
        .join("");
}

function toFileKey(filePath, srcRoot) {
    const relativePath = path
        .relative(srcRoot, filePath)
        .replace(/\\/g, "/")
        .replace(/\.(jsx|js)$/, "");
    return relativePath
        .split("/")
        .filter(Boolean)
        .map((segment) => toKeyPart(segment))
        .join(".");
}

function makeUniqueKey(baseKey, text, flatLocale) {
    if (!Object.hasOwn(flatLocale, baseKey) || flatLocale[baseKey] === text)
        return baseKey;

    let index = 2;
    let key = `${baseKey}${index}`;
    while (Object.hasOwn(flatLocale, key) && flatLocale[key] !== text) {
        index += 1;
        key = `${baseKey}${index}`;
    }
    return key;
}

function isUppercaseName(name) {
    return /^[A-Z]/.test(name || "");
}

function isComponentFunctionPath(functionPath) {
    if (functionPath.isFunctionDeclaration()) {
        return isUppercaseName(functionPath.node.id?.name);
    }

    const parent = functionPath.parentPath;
    if (parent?.isVariableDeclarator()) {
        return isUppercaseName(parent.node.id?.name);
    }

    if (parent?.isExportDefaultDeclaration()) {
        return true;
    }

    return false;
}

function findComponentFunctionPath(pathRef) {
    return pathRef.findParent(
        (parent) => parent.isFunction() && isComponentFunctionPath(parent),
    );
}

function hasUseTranslationHook(functionPath) {
    const body = functionPath.node.body;
    if (!bt.isBlockStatement(body)) return false;

    return body.body.some((statement) => {
        if (!bt.isVariableDeclaration(statement)) return false;
        return statement.declarations.some((declaration) => {
            return (
                bt.isCallExpression(declaration.init) &&
                bt.isIdentifier(declaration.init.callee, {
                    name: "useTranslation",
                })
            );
        });
    });
}

function createUseTranslationHook() {
    return bt.variableDeclaration("const", [
        bt.variableDeclarator(
            bt.objectPattern([
                bt.objectProperty(
                    bt.identifier("t"),
                    bt.identifier("t"),
                    false,
                    true,
                ),
            ]),
            bt.callExpression(bt.identifier("useTranslation"), []),
        ),
    ]);
}

function ensureUseTranslationHook(functionPath) {
    if (hasUseTranslationHook(functionPath)) return;

    if (!bt.isBlockStatement(functionPath.node.body)) {
        const originalBody = functionPath.node.body;
        functionPath.node.body = bt.blockStatement([
            createUseTranslationHook(),
            bt.returnStatement(originalBody),
        ]);
        return;
    }

    functionPath.node.body.body.unshift(createUseTranslationHook());
}

function ensureUseTranslationImport(ast) {
    const body = ast.program.body;
    const existingImport = body.find((statement) => {
        return (
            bt.isImportDeclaration(statement) &&
            statement.source.value === "react-i18next"
        );
    });

    if (existingImport) {
        const hasSpecifier = existingImport.specifiers.some((specifier) => {
            return (
                bt.isImportSpecifier(specifier) &&
                specifier.imported.name === "useTranslation"
            );
        });

        if (!hasSpecifier) {
            existingImport.specifiers.push(
                bt.importSpecifier(
                    bt.identifier("useTranslation"),
                    bt.identifier("useTranslation"),
                ),
            );
        }
        return;
    }

    const importDeclaration = bt.importDeclaration(
        [
            bt.importSpecifier(
                bt.identifier("useTranslation"),
                bt.identifier("useTranslation"),
            ),
        ],
        bt.stringLiteral("react-i18next"),
    );
    const lastImportIndex = body.findLastIndex((statement) =>
        bt.isImportDeclaration(statement),
    );
    body.splice(lastImportIndex + 1, 0, importDeclaration);
}

function createTranslateExpression(key) {
    return bt.jsxExpressionContainer(
        bt.callExpression(bt.identifier("t"), [bt.stringLiteral(key)]),
    );
}

function createWhitespaceExpression(value) {
    return bt.jsxExpressionContainer(bt.stringLiteral(value));
}

function transformFile(filePath, config, flatLocale) {
    const source = fs.readFileSync(filePath, "utf8");
    const ast = parse(source, {
        sourceType: "module",
        plugins: ["jsx", "classProperties", "importMeta"],
    });
    const srcRoot = path.resolve(rootDir, config.src);
    const fileKey = toFileKey(filePath, srcRoot);
    const componentNodes = new Set();
    const additions = [];
    let replacementCount = 0;

    function registerText(rawText, pathRef) {
        const text = normalizeText(rawText);
        if (!shouldTranslateText(text)) return null;

        const componentPath = findComponentFunctionPath(pathRef);
        if (!componentPath) return null;

        const baseKey = makeUniqueKey(
            `${fileKey}.${toKeyPart(text)}`,
            text,
            flatLocale,
        );
        if (!Object.hasOwn(flatLocale, baseKey)) {
            flatLocale[baseKey] = text;
            additions.push(baseKey);
        }

        componentNodes.add(componentPath.node);
        replacementCount += 1;
        return baseKey;
    }

    traverse(ast, {
        JSXText(pathRef) {
            const rawText = pathRef.node.value;
            const key = registerText(rawText, pathRef);
            if (!key) return;

            const nodes = [];
            if (/^\s/.test(rawText))
                nodes.push(createWhitespaceExpression(" "));
            nodes.push(createTranslateExpression(key));
            if (/\s$/.test(rawText))
                nodes.push(createWhitespaceExpression(" "));
            pathRef.replaceWithMultiple(nodes);
        },
        JSXAttribute(pathRef) {
            const attributeName = pathRef.node.name?.name;
            if (!translatableAttributes.has(attributeName)) return;
            if (!bt.isStringLiteral(pathRef.node.value)) return;

            const key = registerText(pathRef.node.value.value, pathRef);
            if (!key) return;

            pathRef.node.value = createTranslateExpression(key);
        },
    });

    if (replacementCount > 0) {
        ensureUseTranslationImport(ast);
        traverse(ast, {
            Function(pathRef) {
                if (componentNodes.has(pathRef.node)) {
                    ensureUseTranslationHook(pathRef);
                }
            },
        });
    }

    const output =
        replacementCount > 0
            ? generate(ast, { jsescOption: { minimal: true } }, source).code
            : source;

    return {
        additions,
        changed: output !== source,
        output,
        replacements: replacementCount,
    };
}

function main() {
    const config = parseArgs(process.argv.slice(2));
    const outPath = path.resolve(rootDir, config.out);
    const locale = readJson(outPath);
    const flatLocale = flattenJson(locale);
    const files =
        config.files.length > 0
            ? config.files.flatMap(collectFiles)
            : collectFiles(config.src);
    const results = [];

    for (const filePath of files) {
        const result = transformFile(filePath, config, flatLocale);
        if (result.replacements > 0) {
            results.push({ filePath, ...result });
            if (config.write && result.changed) {
                fs.writeFileSync(filePath, `${result.output.trimEnd()}\n`);
            }
        }
    }

    if (config.write) {
        writeJson(outPath, unflattenJson(flatLocale));
    }

    const addedKeyCount = results.reduce(
        (total, result) => total + result.additions.length,
        0,
    );
    const replacementCount = results.reduce(
        (total, result) => total + result.replacements,
        0,
    );

    console.log(
        `${config.write ? "Updated" : "Dry run"} ${config.namespace} translations`,
    );
    console.log(`Files scanned: ${files.length}`);
    console.log(`Files with replacements: ${results.length}`);
    console.log(`JSX replacements: ${replacementCount}`);
    console.log(`New locale keys: ${addedKeyCount}`);
    console.log(`Locale file: ${path.relative(rootDir, outPath)}`);

    if (!config.write) {
        console.log("Run again with --write to update JSX and locale JSON.");
    }
}

main();
