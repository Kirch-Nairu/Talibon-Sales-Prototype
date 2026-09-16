export type ShowcasePersona = {
    key: string;
    label: string;
    position: string;
    office: string;
    description: string;
};

export type ShowcaseEntryData = {
    enabled: boolean;
    personas: ShowcasePersona[];
};

export type ShowcaseSessionState = {
    active: boolean;
    persona: ShowcasePersona | null;
};
