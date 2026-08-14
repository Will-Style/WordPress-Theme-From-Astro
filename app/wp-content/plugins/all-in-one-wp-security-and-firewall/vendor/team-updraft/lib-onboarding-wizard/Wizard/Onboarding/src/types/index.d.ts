import type { ReactNode } from 'react';

declare module '@wordpress/element' {
    export interface Component<P = {}, S = {}> {
        render(): ReactNode;
        setState<K extends keyof S>(
            state: ((prevState: Readonly<S>) => Pick<S, K> | S | null) | Pick<S, K> | S | null,
            callback?: () => void
        ): void;
        forceUpdate(callback?: () => void): void;
    }

    export interface ComponentClass<P = {}, S = {}> {
        new (props: P, context?: any): Component<P, S>;
        displayName?: string;
        defaultProps?: Partial<P>;
        contextType?: any;
    }

    export interface FunctionComponent<P = {}> {
        (props: P, context?: any): ReactNode;
        displayName?: string;
        defaultProps?: Partial<P>;
    }

    export function useState<T>(initialState: T | (() => T)): [T, Dispatch<SetStateAction<T>>];
    export function useEffect(effect: EffectCallback, deps?: DependencyList): void;
    export function useMemo<T>(factory: () => T, deps: DependencyList | undefined): T;
    export function memo<P = {}>(
        Component: (props: P, context?: any) => ReactNode,
        propsAreEqual?: (prevProps: P, nextProps: P) => boolean
    ): (props: P, context?: any) => ReactNode;
}

export interface Step {
    id: string;
    type: string;
    documentation?: string;
    groups?: Array<{
        id: string;
        title: string;
        showConfirmButton?: boolean;
        hidden?: boolean;
        controllerFieldId?: string;
        resetOnOpen?: boolean;
    }>;
    icon?: string;
    title: string;
    subtitle: string;
    solutions?: any[]; //used by getCurrentStepSolutions
    title_conditional?: { //used by getConditionalText
        isUpdating?: string;
        isInstalling?: string;
        responseSuccess?: string;
        licenseActivated: string;
    };
    subtitle_conditional?: { //used by getConditionalText
        isUpdating?: string;
        isInstalling?: string;
        responseSuccess?: string;
        licenseActivated: string;
    };
    fields?: SettingField[];
    bullets?: string[];
    intro_bullets?: Array<{
        title: string;
        desc: string;
        icon?: string;
    }>;
    conditional_bullets?: Array<Record<string, string>>;
    enable_premium_btn?: boolean;
    premium_btn_text?: string;
    button?: {
        id: string;
        label: string;
        icon?: string;
        externalAction?: string;
        group_id?: string;
    };
    onContinueExternalAction?: string;
    visible?: boolean;
}

export interface SettingField {
    id: string;
    value?: string | boolean;
    type?: string;
    label?: string;
    default?: string | boolean;
    group_id?: string;
    show_confirm_button?: boolean;
    actionType?: string;
    [key: string]: any;
    options?: Array<{
        id: string;
        value: string;
        label: string;
    }>;
    context_html?: string;
}

export interface OnboardingState {
    isOpen: boolean;
    currentStepIndex: number;
    completedSteps: string[];
    steps: Step[];
    setOpen: (isOpen: boolean) => void;
    setCurrentStepIndex: (index: number) => void;
    addSuccessStep: (stepId: string) => void;
    setSteps: (steps: Step[]) => void;
    isUpdating: boolean;
    setIsUpdating: (status: boolean) => void;
}

export type DependencyList = ReadonlyArray<any>;
export type EffectCallback = () => (void | (() => void | undefined));
export type SetStateAction<S> = S | ((prevState: S) => S);
export type Dispatch<A> = (value: A) => void;