import { useEffect, useState } from "react";
import { useNavigate, useParams } from "react-router-dom";
import { Icon } from "../components/common/Icons.jsx";
import { useToast } from "../components/common/ToastProvider.jsx";
import { apiRequest } from "../api/apiClient.js";
import {
    deliveryOptions,
    gigEditorCategories,
    gigEditorSteps,
    gigEditorSubcategories,
    requirementTypeOptions,
    revisionOptions,
} from "../data/gigEditorData.js";
import { useDashboardStore } from "../stores/useDashboardStore.js";
import { useGigEditorStore } from "../stores/useGigEditorStore.js";

const emptyRequirementDraft = {
    question: "",
    type: "Free text",
    required: true,
    allowMultiple: false,
    options: ["", "", ""],
};

function SellerGigEditorPage() {
    const { gigId } = useParams();
    const navigate = useNavigate();
    const notify = useToast();
    const isEditing = Boolean(gigId);
    const form = useGigEditorStore((state) => state.draft);
    const createGigDraft = useGigEditorStore((state) => state.createGigDraft);
    const updateGigDraft = useGigEditorStore((state) => state.updateGigDraft);
    const setGigDraft = useGigEditorStore((state) => state.setGigDraft);
    const resetGigDraft = useGigEditorStore((state) => state.resetGigDraft);
    const createSellerService = useDashboardStore(
        (state) => state.createSellerService,
    );
    const saveSellerService = useDashboardStore(
        (state) => state.saveSellerService,
    );
    const fetchSellerService = useDashboardStore(
        (state) => state.fetchSellerService,
    );
    const [activeStep, setActiveStep] = useState("overview");
    const [isSaving, setIsSaving] = useState(false);
    const activeStepIndex = gigEditorSteps.findIndex(
        (step) => step.id === activeStep,
    );
    const activeStepMeta = gigEditorSteps[activeStepIndex] || gigEditorSteps[0];

    useEffect(() => {
        window.scrollTo({ top: 0, behavior: "smooth" });
    }, [activeStep]);

    useEffect(() => {
        if (!gigId) {
            createGigDraft();
            setActiveStep("overview");
            return;
        }

        fetchSellerService(gigId).finally(() => {
            createGigDraft(gigId);
            setActiveStep("overview");
        });
        return undefined;
    }, [createGigDraft, fetchSellerService, gigId]);

    const updateField = (field, value) => {
        updateGigDraft(field, value);
    };

    const handleSave = async () => {
        if (activeStep === "overview" && !form.title.trim()) {
            notify.error("Add a clear gig title before saving this step.", {
                title: "Title required",
            });
            return;
        }

        if (activeStepIndex < gigEditorSteps.length - 1) {
            const nextStep = gigEditorSteps[activeStepIndex + 1];
            notify.success(`${activeStepMeta.label} saved.`, {
                title: "Draft updated",
            });
            setActiveStep(nextStep.id);
            return;
        }

        setIsSaving(true);

        try {
            if (isEditing) {
                await saveSellerService(gigId, form);
            } else {
                await createSellerService(form);
            }
        } catch {
            notify.error("We could not save this gig. Please try again.", {
                title: "Save failed",
            });
            setIsSaving(false);
            return;
        }

        setIsSaving(false);
        resetGigDraft();
        notify.success(
            isEditing
                ? "Your gig changes have been saved."
                : "Your new gig draft has been saved.",
            {
                title: isEditing ? "Gig updated" : "Gig created",
            },
        );
        navigate("/dashboard/seller/services");
    };

    const handleCancel = () => {
        resetGigDraft();
        notify.info("Gig editor closed.");
        navigate("/dashboard/seller/services");
    };

    return (
        <main className="dashboard-content gig-editor-page">
            <StepTabs activeStep={activeStep} onChange={setActiveStep} />
            <div className="gig-editor-shell">
                {activeStep === "overview" ? (
                    <OverviewStep form={form} onUpdate={updateField} />
                ) : null}
                {activeStep === "pricing" ? (
                    <PricingStep form={form} onUpdate={setGigDraft} />
                ) : null}
                {activeStep === "description" ? (
                    <DescriptionFaqStep form={form} onUpdate={setGigDraft} />
                ) : null}
                {activeStep === "requirements" ? (
                    <RequirementsStep
                        form={form}
                        onUpdate={setGigDraft}
                        notify={notify}
                    />
                ) : null}
                {activeStep === "gallery" ? (
                    <GalleryStep
                        form={form}
                        onUpdate={setGigDraft}
                        notify={notify}
                    />
                ) : null}
            </div>
            <EditorActions
                isSaving={isSaving}
                onCancel={handleCancel}
                onSave={handleSave}
            />
        </main>
    );
}

function StepTabs({ activeStep, onChange }) {
    return (
        <header className="gig-editor-topbar">
            <nav className="gig-editor-tabs" aria-label="Gig setup steps">
                {gigEditorSteps.map((step) => (
                    <button
                        className={activeStep === step.id ? "active" : ""}
                        type="button"
                        aria-current={activeStep === step.id ? "step" : undefined}
                        key={step.id}
                        onClick={() => onChange(step.id)}
                    >
                        {step.label}
                    </button>
                ))}
            </nav>
        </header>
    );
}

function OverviewStep({ form, onUpdate }) {
    const [tagDraft, setTagDraft] = useState("");

    const addTag = () => {
        const nextTag = tagDraft.trim();

        if (!nextTag || form.tags.length >= 5) return;

        onUpdate("tags", [...form.tags, nextTag]);
        setTagDraft("");
    };

    const removeTag = (tag) => {
        onUpdate(
            "tags",
            form.tags.filter((currentTag) => currentTag !== tag),
        );
    };

    return (
        <section className="gig-editor-card overview-editor-card">
            <div className="gig-form-row">
                <div className="gig-field-copy">
                    <h1>Gig title</h1>
                    <p>
                        As your Gig storefront, your title is the most important
                        place to include keywords that buyers would likely use to
                        search for a service like yours.
                    </p>
                </div>
                <div className="gig-field-control">
                    <div className="gig-ai-input">
                        <textarea
                            value={form.title}
                            maxLength={80}
                            rows={2}
                            onChange={(event) =>
                                onUpdate("title", event.target.value)
                            }
                        />
                        <button type="button" aria-label="Improve title">
                            <Icon name="spark" />
                        </button>
                    </div>
                    <div className="gig-input-hint-row">
                        <span>Just perfect!</span>
                        <span>{form.title.length} / 80 max</span>
                    </div>
                </div>
            </div>

            <div className="gig-form-row">
                <div className="gig-field-copy">
                    <h2>Category</h2>
                    <p>
                        Choose the category and sub-category most suitable for
                        your Gig.
                    </p>
                </div>
                <div className="gig-select-grid">
                    <SelectField
                        label="Category"
                        value={form.category}
                        options={gigEditorCategories}
                        onChange={(value) => onUpdate("category", value)}
                    />
                    <SelectField
                        label="Sub-category"
                        value={form.subcategory}
                        options={gigEditorSubcategories}
                        onChange={(value) => onUpdate("subcategory", value)}
                    />
                </div>
            </div>

            <div className="gig-form-row">
                <div className="gig-field-copy">
                    <h2>Search tags</h2>
                    <p>
                        Tag your Gig with buzz words that are relevant to the
                        services you offer. Use all 5 tags to get found.
                    </p>
                </div>
                <div className="gig-field-control">
                    <h3>Positive keywords</h3>
                    <p>
                        Enter search terms you feel your buyers will use when
                        looking for your service.
                    </p>
                    <div className="gig-tag-input">
                        {form.tags.map((tag) => (
                            <button
                                type="button"
                                key={tag}
                                onClick={() => removeTag(tag)}
                            >
                                {tag}
                                <span aria-hidden="true">x</span>
                            </button>
                        ))}
                        {form.tags.length < 5 ? (
                            <input
                                type="text"
                                value={tagDraft}
                                aria-label="Add search tag"
                                placeholder="Add tag"
                                onBlur={addTag}
                                onChange={(event) =>
                                    setTagDraft(event.target.value)
                                }
                                onKeyDown={(event) => {
                                    if (event.key === "Enter") {
                                        event.preventDefault();
                                        addTag();
                                    }
                                }}
                            />
                        ) : null}
                    </div>
                    <small>5 tags maximum. Use letters and numbers only.</small>
                </div>
            </div>
        </section>
    );
}

function PricingStep({ form, onUpdate }) {
    const updatePackage = (packageId, field, value) => {
        onUpdate((currentForm) => ({
            ...currentForm,
            packages: currentForm.packages.map((item) =>
                item.id === packageId ? { ...item, [field]: value } : item,
            ),
        }));
    };

    const toggleExtra = (extraId) => {
        onUpdate((currentForm) => ({
            ...currentForm,
            extras: currentForm.extras.map((extra) =>
                extra.id === extraId
                    ? { ...extra, enabled: !extra.enabled }
                    : extra,
            ),
        }));
    };

    const updateExtraRow = (extraId, packageId, field, value) => {
        onUpdate((currentForm) => ({
            ...currentForm,
            extras: currentForm.extras.map((extra) =>
                extra.id === extraId
                    ? {
                          ...extra,
                          rows: extra.rows.map((row) =>
                              row.packageId === packageId
                                  ? { ...row, [field]: value }
                                  : row,
                          ),
                      }
                    : extra,
            ),
        }));
    };

    return (
        <section className="pricing-editor">
            <div className="gig-section-heading">
                <h1>Scope & Pricing</h1>
                <label className="gig-switch">
                    <span>Offer packages</span>
                    <input type="checkbox" checked readOnly />
                    <i aria-hidden="true"></i>
                </label>
            </div>

            <section className="pricing-block" aria-labelledby="packagesTitle">
                <h2 id="packagesTitle">Packages</h2>
                <div className="pricing-package-grid">
                    <div className="pricing-package-cell pricing-corner"></div>
                    {form.packages.map((item) => (
                        <div
                            className="pricing-package-cell package-heading"
                            key={item.id}
                        >
                            {item.label}
                        </div>
                    ))}

                    <div className="pricing-row-label">Package</div>
                    {form.packages.map((item) => (
                        <div className="pricing-package-cell" key={item.id}>
                            <input
                                value={item.name}
                                aria-label={`${item.label} package name`}
                                onChange={(event) =>
                                    updatePackage(
                                        item.id,
                                        "name",
                                        event.target.value,
                                    )
                                }
                            />
                        </div>
                    ))}

                    <div className="pricing-row-label tall">Description</div>
                    {form.packages.map((item) => (
                        <div
                            className="pricing-package-cell tall"
                            key={`${item.id}-description`}
                        >
                            <textarea
                                value={item.description}
                                rows={4}
                                aria-label={`${item.label} package description`}
                                onChange={(event) =>
                                    updatePackage(
                                        item.id,
                                        "description",
                                        event.target.value,
                                    )
                                }
                            />
                        </div>
                    ))}

                    <div className="pricing-row-label">Delivery</div>
                    {form.packages.map((item) => (
                        <div
                            className="pricing-package-cell"
                            key={`${item.id}-delivery`}
                        >
                            <SelectField
                                label={`${item.label} delivery`}
                                value={item.delivery}
                                options={[
                                    "1 Day Delivery",
                                    "3 Days Delivery",
                                    "7 Days Delivery",
                                    "14 Days Delivery",
                                    "21 Days Delivery",
                                ]}
                                onChange={(value) =>
                                    updatePackage(item.id, "delivery", value)
                                }
                            />
                        </div>
                    ))}

                    <div className="pricing-row-label">Revisions</div>
                    {form.packages.map((item) => (
                        <div
                            className="pricing-package-cell"
                            key={`${item.id}-revisions`}
                        >
                            <SelectField
                                label={`${item.label} revisions`}
                                value={item.revisions}
                                options={revisionOptions}
                                onChange={(value) =>
                                    updatePackage(item.id, "revisions", value)
                                }
                            />
                        </div>
                    ))}

                    <div className="pricing-row-label">Price</div>
                    {form.packages.map((item) => (
                        <div
                            className="pricing-package-cell"
                            key={`${item.id}-price`}
                        >
                            <label className="money-input">
                                <span>$</span>
                                <input
                                    value={item.price}
                                    inputMode="numeric"
                                    aria-label={`${item.label} package price`}
                                    onChange={(event) =>
                                        updatePackage(
                                            item.id,
                                            "price",
                                            event.target.value,
                                        )
                                    }
                                />
                            </label>
                        </div>
                    ))}
                </div>
            </section>

            <section className="pricing-block" aria-labelledby="extrasTitle">
                <h2 id="extrasTitle">Add extra services</h2>
                <div className="gig-extra-panel">
                    {form.extras.map((extra) => (
                        <div className="gig-extra-item" key={extra.id}>
                            <label className="gig-checkbox-label">
                                <input
                                    type="checkbox"
                                    checked={extra.enabled}
                                    onChange={() => toggleExtra(extra.id)}
                                />
                                <span>{extra.label}</span>
                            </label>

                            {extra.enabled ? (
                                <div className="gig-extra-rows">
                                    {form.packages.map((item) => {
                                        const row = extra.rows.find(
                                            (extraRow) =>
                                                extraRow.packageId === item.id,
                                        );

                                        return (
                                            <div
                                                className="gig-extra-row"
                                                key={`${extra.id}-${item.id}`}
                                            >
                                                <span>{item.label}</span>
                                                <span>I'll deliver in only</span>
                                                <SelectField
                                                    label={`${extra.label} ${item.label} delivery`}
                                                    value={
                                                        row?.delivery ||
                                                        "Select"
                                                    }
                                                    options={deliveryOptions}
                                                    onChange={(value) =>
                                                        updateExtraRow(
                                                            extra.id,
                                                            item.id,
                                                            "delivery",
                                                            value,
                                                        )
                                                    }
                                                />
                                                <span>for an extra</span>
                                                <label className="money-input">
                                                    <span>$</span>
                                                    <input
                                                        value={row?.price || ""}
                                                        inputMode="numeric"
                                                        aria-label={`${extra.label} ${item.label} price`}
                                                        onChange={(event) =>
                                                            updateExtraRow(
                                                                extra.id,
                                                                item.id,
                                                                "price",
                                                                event.target
                                                                    .value,
                                                            )
                                                        }
                                                    />
                                                </label>
                                            </div>
                                        );
                                    })}
                                </div>
                            ) : null}
                        </div>
                    ))}
                    <button className="gig-add-link" type="button">
                        <Icon name="plus" />
                        Add Gig Extra
                    </button>
                </div>
            </section>
        </section>
    );
}

function DescriptionFaqStep({ form, onUpdate }) {
    const [openFaqId, setOpenFaqId] = useState(form.faqs[0]?.id);

    const updateDescription = (value) => {
        onUpdate((currentForm) => ({
            ...currentForm,
            description: value,
        }));
    };

    const addFaq = () => {
        const newFaq = {
            id: `faq-${Date.now()}`,
            question: "Add your buyer question here",
            answer: "Write a clear, helpful answer for buyers.",
        };

        onUpdate((currentForm) => ({
            ...currentForm,
            faqs: [...currentForm.faqs, newFaq],
        }));
        setOpenFaqId(newFaq.id);
    };

    const updateFaq = (faqId, field, value) => {
        onUpdate((currentForm) => ({
            ...currentForm,
            faqs: currentForm.faqs.map((faq) =>
                faq.id === faqId ? { ...faq, [field]: value } : faq,
            ),
        }));
    };

    return (
        <section className="gig-editor-narrow">
            <div className="gig-section-heading simple">
                <h1>Description</h1>
            </div>

            <div className="description-field">
                <label htmlFor="gigDescription">Briefly Describe Your Gig</label>
                <div className="rich-text-shell">
                    <div className="rich-text-toolbar" aria-hidden="true">
                        <span>B</span>
                        <span>I</span>
                        <span>*</span>
                        <span>1.</span>
                        <span>--</span>
                    </div>
                    <textarea
                        id="gigDescription"
                        value={form.description}
                        maxLength={1200}
                        rows={10}
                        onChange={(event) =>
                            updateDescription(event.target.value)
                        }
                    />
                </div>
                <span className="description-count">
                    {form.description.length}/1200 Characters
                </span>
            </div>

            <section className="faq-editor" aria-labelledby="faqTitle">
                <div className="faq-heading-row">
                    <div>
                        <h2 id="faqTitle">Frequently Asked Questions</h2>
                        <p>Add Questions & Answers for Your Buyers.</p>
                    </div>
                    <button type="button" onClick={addFaq}>
                        + Add FAQ
                    </button>
                </div>

                <div className="faq-list">
                    {form.faqs.map((faq) => (
                        <article className="faq-item" key={faq.id}>
                            <button
                                className="faq-summary"
                                type="button"
                                aria-expanded={openFaqId === faq.id}
                                onClick={() =>
                                    setOpenFaqId((currentId) =>
                                        currentId === faq.id ? null : faq.id,
                                    )
                                }
                            >
                                <span aria-hidden="true">=</span>
                                <strong>{faq.question}</strong>
                                <Icon name="chevronDown" />
                            </button>
                            {openFaqId === faq.id ? (
                                <div className="faq-answer-fields">
                                    <input
                                        value={faq.question}
                                        aria-label="FAQ question"
                                        onChange={(event) =>
                                            updateFaq(
                                                faq.id,
                                                "question",
                                                event.target.value,
                                            )
                                        }
                                    />
                                    <textarea
                                        value={faq.answer}
                                        rows={3}
                                        aria-label="FAQ answer"
                                        onChange={(event) =>
                                            updateFaq(
                                                faq.id,
                                                "answer",
                                                event.target.value,
                                            )
                                        }
                                    />
                                </div>
                            ) : null}
                        </article>
                    ))}
                </div>
            </section>
        </section>
    );
}

function RequirementsStep({ form, onUpdate, notify }) {
    const [isNoticeVisible, setIsNoticeVisible] = useState(true);
    const [isAddingQuestion, setIsAddingQuestion] = useState(false);
    const [questionDraft, setQuestionDraft] = useState(emptyRequirementDraft);

    const updateQuestionDraft = (field, value) => {
        setQuestionDraft((currentDraft) => ({
            ...currentDraft,
            [field]: value,
            options:
                field === "type" && value === "Multiple choice"
                    ? currentDraft.options.length
                        ? currentDraft.options
                        : ["", "", ""]
                    : currentDraft.options,
        }));
    };

    const updateQuestionOption = (index, value) => {
        setQuestionDraft((currentDraft) => ({
            ...currentDraft,
            options: currentDraft.options.map((option, optionIndex) =>
                optionIndex === index ? value : option,
            ),
        }));
    };

    const addQuestionOption = () => {
        setQuestionDraft((currentDraft) => ({
            ...currentDraft,
            options: [...currentDraft.options, ""],
        }));
    };

    const removeQuestionOption = (index) => {
        setQuestionDraft((currentDraft) => ({
            ...currentDraft,
            options: currentDraft.options.filter(
                (option, optionIndex) => optionIndex !== index,
            ),
        }));
    };

    const addRequirementQuestion = () => {
        if (!questionDraft.question.trim()) {
            notify.error("Write the buyer question before adding it.", {
                title: "Question required",
            });
            return;
        }

        const cleanOptions = questionDraft.options.filter((option) =>
            option.trim(),
        );

        if (
            questionDraft.type === "Multiple choice" &&
            cleanOptions.length < 2
        ) {
            notify.error("Add at least two options for multiple choice.");
            return;
        }

        onUpdate((currentForm) => ({
            ...currentForm,
            requirements: [
                ...currentForm.requirements,
                {
                    id: `requirement-${Date.now()}`,
                    type: questionDraft.type,
                    question: questionDraft.question,
                    detail:
                        questionDraft.type === "Multiple choice"
                            ? cleanOptions.join(", ")
                            : "",
                    required: questionDraft.required,
                    allowMultiple: questionDraft.allowMultiple,
                    options: cleanOptions,
                },
            ],
        }));
        setQuestionDraft(emptyRequirementDraft);
        setIsAddingQuestion(false);
        notify.success("Requirement question added.");
    };

    return (
        <section className="requirements-editor">
            <div className="requirements-panel">
                <header className="requirements-heading">
                    <h1>
                        Get all the information you need from buyers to get
                        started
                    </h1>
                    <p>
                        Add questions to help buyers provide you with exactly
                        what you need to start working on their order.
                    </p>
                </header>

                <DividerLabel label="Questions" />
                <p className="requirements-note">
                    These optional questions will be added for all buyers.
                </p>
                <QuestionList
                    questions={form.platformQuestions}
                    offset={0}
                    readOnly
                />

                <DividerLabel label="Your questions" />
                {isNoticeVisible ? (
                    <div className="requirement-alert">
                        <span aria-hidden="true">i</span>
                        <p>
                            Take a moment to make sure your questions aren't
                            asking for the same information requested above.
                        </p>
                        <button
                            type="button"
                            onClick={() => setIsNoticeVisible(false)}
                        >
                            Dismiss
                        </button>
                    </div>
                ) : null}

                <QuestionList
                    questions={form.requirements}
                    offset={0}
                    readOnly={false}
                />

                {isAddingQuestion ? (
                    <AddQuestionPanel
                        draft={questionDraft}
                        onAdd={addRequirementQuestion}
                        onCancel={() => setIsAddingQuestion(false)}
                        onOptionAdd={addQuestionOption}
                        onOptionRemove={removeQuestionOption}
                        onOptionUpdate={updateQuestionOption}
                        onUpdate={updateQuestionDraft}
                    />
                ) : (
                    <button
                        className="gig-outline-action"
                        type="button"
                        onClick={() => setIsAddingQuestion(true)}
                    >
                        <Icon name="plus" />
                        Add New Question
                    </button>
                )}
            </div>
        </section>
    );
}

function GalleryStep({ form, onUpdate, notify }) {
    const uploadMedia = async (file, type) => {
        if (!file) return;

        const formData = new FormData();
        formData.append("file", file);
        formData.append("type", type);

        return apiRequest("/api/seller/services/media", {
            body: formData,
        });
    };
    const updateGalleryImage = async (index, file) => {
        if (!file) return;

        let uploadedMedia = null;

        try {
            uploadedMedia = await uploadMedia(file, "image");
        } catch {
            notify.error("Image upload failed. Please try again.");
            return;
        }

        onUpdate((currentForm) => ({
            ...currentForm,
            ...replaceMediaAtIndex(currentForm, uploadedMedia, index, "image"),
        }));
        notify.success("Gallery image updated.");
    };
    const updateSingleVideo = async (file) => {
        if (!file) return;

        let uploadedMedia = null;

        try {
            uploadedMedia = await uploadMedia(file, "video");
        } catch {
            notify.error("Video upload failed. Please try again.");
            return;
        }

        onUpdate((currentForm) => ({
            ...currentForm,
            media: [
                ...(currentForm.media || []).filter(
                    (item) => item.type !== "video",
                ),
                uploadedMedia,
            ],
        }));
        notify.success("Video uploaded.");
    };
    const updateDocument = async (file) => {
        if (!file) return;

        let uploadedMedia = null;

        try {
            uploadedMedia = await uploadMedia(file, "document");
        } catch {
            notify.error("Document upload failed. Please try again.");
            return;
        }

        onUpdate((currentForm) => ({
            ...currentForm,
            media: [
                ...(currentForm.media || []).filter(
                    (item) => item.type !== "document",
                ),
                uploadedMedia,
            ].slice(0, 12),
        }));
        notify.success("Document uploaded.");
    };
    const gallerySlots = Array.from(
        { length: 3 },
        (_, index) => form.galleryImages[index] || "",
    );
    const video = form.media?.find((item) => item.type === "video");
    const documents = (form.media || []).filter(
        (item) => item.type === "document",
    );

    return (
        <section className="gallery-editor">
            <div className="gallery-heading">
                <h1>Showcase Your Services In A Gig Gallery</h1>
                <p>
                    Encourage buyers to choose your Gig by featuring a variety
                    of your work.
                </p>
            </div>

            <div className="gallery-policy">
                <span aria-hidden="true">i</span>
                <p>
                    To comply with bdgigs terms of service, make sure to upload
                    only content you either own or have the permission or license
                    to use.
                </p>
            </div>

            <a className="gallery-guideline-link" href="#gallery-guidelines">
                Gig image guidelines
            </a>

            <section className="gallery-section" id="gallery-guidelines">
                <h2>Images (up to 3)</h2>
                <p>
                    Get noticed by the right buyers with visual examples of your
                    services.
                </p>
                <div className="gallery-image-grid">
                    {gallerySlots.map((image, index) => (
                        <label
                            className={`gallery-image-tile${image ? "" : " is-empty"}`}
                            key={`gallery-slot-${index}`}
                        >
                            {image ? (
                                <img
                                    src={image}
                                    alt={`Gig gallery example ${index + 1}`}
                                    loading="lazy"
                                    decoding="async"
                                />
                            ) : (
                                <span>
                                    <Icon name="plus" />
                                    Upload image
                                </span>
                            )}
                            {index === 0 ? <span>Primary</span> : null}
                            <input
                                type="file"
                                accept="image/*"
                                onChange={(event) =>
                                    updateGalleryImage(
                                        index,
                                        event.target.files?.[0],
                                    )
                                }
                            />
                        </label>
                    ))}
                </div>
            </section>

            <section className="gallery-section">
                <h2>Video (one only)</h2>
                <p>
                    Capture buyers' attention with a video that showcases your
                    service.
                </p>
                <small>
                    Please choose a video shorter than 75 seconds and smaller
                    than 50MB
                </small>
                <UploadBox
                    accept="video/*"
                    icon="video"
                    label="Drag & drop a Video or"
                    onChange={updateSingleVideo}
                />
                {video ? <em>{video.originalName || "Video uploaded"}</em> : null}
            </section>

            <section className="gallery-section">
                <h2>Documents (up to 2)</h2>
                <p>
                    Show some of the best work you created in a document (PDFs
                    only).
                </p>
                <div className="document-upload-grid">
                    <UploadBox
                        accept="application/pdf"
                        icon="document"
                        label="Drag & drop a PDF or"
                        onChange={updateDocument}
                    />
                    <div className="empty-document-slot">
                        {documents.length ? (
                            <span>{documents.length} document uploaded</span>
                        ) : null}
                    </div>
                </div>
            </section>
        </section>
    );
}

function replaceMediaAtIndex(form, uploadedMedia, index, type) {
    const media = form.media || [];
    const targetMedia = media.filter((item) => item.type === type);
    const otherMedia = media.filter((item) => item.type !== type);
    const nextTargetMedia = Array.from({ length: 3 }, (_, slotIndex) =>
        slotIndex === index ? uploadedMedia : targetMedia[slotIndex],
    ).filter(Boolean);

    return {
        media: [
            ...nextTargetMedia.map((item, itemIndex) => ({
                ...item,
                primary: itemIndex === 0,
            })),
            ...otherMedia,
        ],
        galleryImages: nextTargetMedia.map((item) => item.url),
    };
}

function AddQuestionPanel({
    draft,
    onAdd,
    onCancel,
    onOptionAdd,
    onOptionRemove,
    onOptionUpdate,
    onUpdate,
}) {
    return (
        <section className="add-question-panel">
            <div className="add-question-head">
                <h2>Add a question</h2>
                <label className="gig-checkbox-label">
                    <input
                        type="checkbox"
                        checked={draft.required}
                        onChange={(event) =>
                            onUpdate("required", event.target.checked)
                        }
                    />
                    <span>Required</span>
                </label>
            </div>
            <div className="gig-ai-input">
                <textarea
                    value={draft.question}
                    maxLength={400}
                    rows={2}
                    placeholder="Request necessary details such as dimensions, brand guidelines, and more."
                    onChange={(event) =>
                        onUpdate("question", event.target.value)
                    }
                />
                <button type="button" aria-label="Improve question">
                    <Icon name="spark" />
                </button>
            </div>
            <span className="question-count">
                {draft.question.length}/400 characters
            </span>

            <div className="question-form-row">
                <div className="question-type-field">
                    <span>Get it in a form of:</span>
                    <SelectField
                        label="Question type"
                        value={draft.type}
                        options={requirementTypeOptions}
                        onChange={(value) => onUpdate("type", value)}
                    />
                </div>

                {draft.type === "Multiple choice" ? (
                    <label className="gig-checkbox-label">
                        <input
                            type="checkbox"
                            checked={draft.allowMultiple}
                            onChange={(event) =>
                                onUpdate(
                                    "allowMultiple",
                                    event.target.checked,
                                )
                            }
                        />
                        <span>Enable to choose more than 1 option</span>
                    </label>
                ) : null}
            </div>

            {draft.type === "Multiple choice" ? (
                <div className="question-options">
                    {draft.options.map((option, index) => (
                        <label key={`option-${index}`}>
                            <span className="sr-only">
                                Option {index + 1}
                            </span>
                            <input
                                type="text"
                                value={option}
                                placeholder="Add Option"
                                onChange={(event) =>
                                    onOptionUpdate(index, event.target.value)
                                }
                            />
                            <button
                                type="button"
                                aria-label={`Remove option ${index + 1}`}
                                onClick={() => onOptionRemove(index)}
                            >
                                x
                            </button>
                        </label>
                    ))}
                    <button
                        className="gig-outline-action"
                        type="button"
                        onClick={onOptionAdd}
                    >
                        <Icon name="plus" />
                        Add New Option
                    </button>
                </div>
            ) : null}

            <div className="add-question-actions">
                <button className="gig-secondary-action" type="button" onClick={onCancel}>
                    Cancel
                </button>
                <button className="gig-primary-action" type="button" onClick={onAdd}>
                    Add
                </button>
            </div>
        </section>
    );
}

function QuestionList({ questions, offset = 0 }) {
    return (
        <div className="requirement-question-list">
            {questions.map((question, index) => (
                <article
                    className="requirement-question-card"
                    key={question.id}
                >
                    <div className="requirement-card-type">
                        <span aria-hidden="true">::</span>
                        <strong>{question.type}</strong>
                        <Icon name="moreHorizontal" />
                    </div>
                    <p>
                        <strong>
                            {offset + index + 1}. {question.question}
                        </strong>
                    </p>
                    {question.detail ? <p>{question.detail}</p> : null}
                </article>
            ))}
        </div>
    );
}

function DividerLabel({ label }) {
    return (
        <div className="gig-divider-label">
            <span>{label}</span>
        </div>
    );
}

function UploadBox({ accept, icon, label, onChange }) {
    return (
        <label className="upload-box">
            <Icon name={icon} />
            <span>
                {label} <strong>Browse</strong>
            </span>
            <input
                type="file"
                accept={accept}
                onChange={(event) => onChange(event.target.files?.[0])}
            />
        </label>
    );
}

function SelectField({ label, value, options, onChange }) {
    return (
        <label className="select-field">
            <span className="sr-only">{label}</span>
            <select
                value={value}
                aria-label={label}
                onChange={(event) => onChange(event.target.value)}
            >
                {options.map((option) => (
                    <option value={option} key={option}>
                        {option}
                    </option>
                ))}
            </select>
            <Icon name="chevronDown" />
        </label>
    );
}

function EditorActions({ isSaving, onCancel, onSave }) {
    return (
        <footer className="gig-editor-actions">
            <button className="gig-secondary-action" type="button" onClick={onCancel}>
                Cancel
            </button>
            <button
                className="gig-primary-action"
                type="button"
                disabled={isSaving}
                onClick={onSave}
            >
                {isSaving ? "Saving..." : "Save"}
            </button>
        </footer>
    );
}

export default SellerGigEditorPage;
