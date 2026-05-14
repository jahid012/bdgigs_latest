import i18n from "i18next";
import LanguageDetector from "i18next-browser-languagedetector";
import { initReactI18next } from "react-i18next";
import bnCommon from "../locales/bn/common.json";
import enCommon from "../locales/en/common.json";

export const supportedLanguages = [
    { code: "en", label: "EN", name: "English", dir: "ltr" },
    { code: "bn", label: "BN", name: "Bangla", dir: "ltr" },
];

const resources = {
    en: { common: enCommon },
    bn: { common: bnCommon },
};

function normalizeLanguage(language) {
    return String(language || "en").split("-")[0];
}

function getLanguageDirection(language) {
    const code = normalizeLanguage(language);
    return supportedLanguages.find((item) => item.code === code)?.dir || "ltr";
}

function applyDocumentLanguage(language) {
    if (typeof document === "undefined") return;

    const code = normalizeLanguage(language);
    const direction = getLanguageDirection(code);

    document.documentElement.lang = code;
    document.documentElement.dir = direction;
    document.body?.classList.toggle("is-rtl", direction === "rtl");
}

i18n.use(LanguageDetector)
    .use(initReactI18next)
    .init({
        resources,
        fallbackLng: "en",
        supportedLngs: supportedLanguages.map((language) => language.code),
        defaultNS: "common",
        ns: ["common"],
        interpolation: {
            escapeValue: false,
        },
        detection: {
            order: ["localStorage", "navigator"],
            caches: ["localStorage"],
            lookupLocalStorage: "bdgigs_language",
        },
    });

applyDocumentLanguage(i18n.resolvedLanguage || i18n.language);
i18n.on("languageChanged", applyDocumentLanguage);

export default i18n;
