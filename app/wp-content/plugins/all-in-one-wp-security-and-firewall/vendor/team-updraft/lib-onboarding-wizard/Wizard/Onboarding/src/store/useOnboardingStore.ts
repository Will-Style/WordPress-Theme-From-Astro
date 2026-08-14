import {create} from 'zustand';
import {updateAction, adminAjaxRequest} from '../utils/api';
import type {Step} from "../types";
import {__} from "@wordpress/i18n";

type OptionLike = { action?: string; [key: string]: any };

const getActionNiceName = (action: string, plugin: string ) => {
    let actionNiceName = '';
    switch (action) {
        case 'download':
            actionNiceName = __('...installing %s', 'ONBOARDING_WIZARD_TEXT_DOMAIN').replace('%s', plugin);
            break;
        case 'activate':
            actionNiceName = __('...activating %s', 'ONBOARDING_WIZARD_TEXT_DOMAIN').replace('%s', plugin);
            break;
        case 'upgrade-to-pro':
        case 'installed':
        default:
            actionNiceName = '';
    }
    return actionNiceName;
}

export interface OnboardingState {
    isUpdating: boolean;
    setIsUpdating: (status: boolean) => void;
    isInstalling: boolean;
    areAllInstalled: (options: OptionLike[]) => boolean;
    responseMessage: string,
    responseCode: boolean | string,
    footerMessage: string,
    responseSuccess: boolean,
    setResponseSuccess: (success: boolean) => void;
    setFooterMessage: (message: string) => void;
    onboardingData: Record<string, any>;
    isOpen: boolean;
    currentStepIndex: number;
    completedSteps: string[];
    steps: Step[];
    settings: Array<any>;
    trackingTestRunning: boolean;
    trackingTestCompleted: boolean;
    trackingTestSuccess: boolean;
    setOpen: (isOpen: boolean) => void;
    setCurrentStepIndex: (index: number) => void;
    getCurrentStep: () => Step;
    getCurrentStepDocumentation: () => string;
    getCurrentStepSolutions: () => [];
    setResponseMessage: (message: string) => void;
    addSuccessStep: (stepId: string) => void;
    getValue: (id: string) => string|boolean|any;
    isEdited: (id: string) => boolean;
    setValue: (id: string, value: any) => void;
    setSteps: (steps: Step[]) => void;
    updateEmail: () => Promise<void>;
    getSettings: () => Array<any>;
    updateStepSettings: (settings: Array<any> ) => Promise<void>;
    getPluginsStep: () => Step | undefined;
    updatePluginAction: () => Promise<void>;
    installPlugins: () => Promise<void>;
    validateLicense: () => Promise<boolean>;
    licenseStatus: string;
    isLastStep: () => boolean;
    wizardCompletedOnce: boolean;
    setTrackingTestRunning: (running: boolean) => void;
    setTrackingTestCompleted: (completed: boolean) => void;
    setTrackingTestSuccess: (success: boolean) => void;
    continueDisabled: boolean;
    setContinueDisabled: (disabled: boolean) => void;
    getConditionalText: (
        step: Step,
        key: 'title' | 'subtitle',
        opts?: Partial<{
            isUpdating: boolean;
            isInstalling: boolean;
            responseMessage: string;
            responseSuccess: boolean;
            licenseStatus: string;
        }>,
        fallback?: string
    ) => string;
}

// Zustand store
const useOnboardingStore = create<OnboardingState>((set) => ({
    isOpen: false,
    isUpdating: false,
    setIsUpdating: (status) => set({ isUpdating: status }),
    isInstalling: false,
    wizardCompletedOnce: false,
    currentStepIndex: 0,
    responseMessage: '',
    responseCode: false,
    footerMessage:'',
    trackingTestRunning: false,
    trackingTestCompleted: false,
    trackingTestSuccess: false,
    continueDisabled: false,
    licenseStatus: 'none',
    setContinueDisabled: (disabled) => set({ continueDisabled: disabled }),
    setFooterMessage: (message) => set({ footerMessage: message }),
    setResponseMessage: (message) => set({ responseMessage: message }),
    setTrackingTestRunning: (running) => set({ trackingTestRunning: running }),
    setTrackingTestCompleted: (completed) => set({ trackingTestCompleted: completed }),
    setTrackingTestSuccess: (success) => set({ trackingTestSuccess: success }),
    getCurrentStep: () => {
        const state = useOnboardingStore.getState();
        const visibleSteps = state.steps?.filter(step => step.visible !== false) || [];
        return visibleSteps[state.currentStepIndex];
    },
    getCurrentStepDocumentation: () => {
        const state = useOnboardingStore.getState();
        let stepDocumentation = state.steps[state.currentStepIndex]?.documentation;
        return stepDocumentation || state.onboardingData.documentation;
    },
    getCurrentStepSolutions: () => {
        const state = useOnboardingStore.getState();
        let stepSolutions = state.steps[state.currentStepIndex]?.solutions;
        return stepSolutions || [];
    },
    responseSuccess: true,
    setResponseSuccess: (success) => set({ responseSuccess: success }),
    completedSteps: [],
    onboardingData: window[`teamupdraft_onboarding`] || {},
    steps: [],
    setOpen: (isOpen) => set({ isOpen }),
    setCurrentStepIndex: (index) => set({ currentStepIndex: index }),
    addSuccessStep: (stepId) => set((state) => ({
        completedSteps: [...state.completedSteps, stepId]
    })),
    isLastStep: () => {
        const state = useOnboardingStore.getState();
        const maybeLast = state.currentStepIndex === (state.steps.length - 1);

        if (maybeLast && !state.wizardCompletedOnce) {
            set({ wizardCompletedOnce: true });
            // if its last step, update the DB that user completed the setup
            updateAction({}, 'user_completed_wizard');
        }

        return maybeLast;
    },
    settings: [],
    getSettings: () => {
        const state = useOnboardingStore.getState();
        if (state.settings.length > 0) {
            return state.settings;
        }
        const settings = state.onboardingData.fields;
        set({ settings: settings });
        return settings;
    },
    getValue: (id: string) => {
        const state = useOnboardingStore.getState();
        return state.settings.find((field) => field.id === id)?.value;
    },
    isEdited: (id: string) => {
        const state = useOnboardingStore.getState();
        return !!state.settings.find((field) => field.id === id)?.edited;
    },
    setValue: (id: string, value: any) => {
        const state = useOnboardingStore.getState();
        const currentSettings = state.getSettings();

        const existingFieldIndex = currentSettings.findIndex((field) => field.id === id);

        let updatedSettings;
        if (existingFieldIndex !== -1) {
            // Update existing field
            updatedSettings = currentSettings.map((field) =>
                field.id === id ? { ...field, value, edited: true } : field
            );
        } else {
            // Add new field if it doesn't exist
            updatedSettings = [...currentSettings, { id, value, edited: true }];
        }

        set({ settings: updatedSettings });
    },
    updateStepSettings: async (settings) => {
        set({ isUpdating: true });
        const currentStep = useOnboardingStore.getState().getCurrentStep();
        let data = {
            step:currentStep.id,
            settings:settings,
        }

        await updateAction( data, 'update_settings' );
        set({ isUpdating: false, settings: settings });
    },
    updateEmail: async () => {
        set({ isUpdating: true });
        const currentStep = useOnboardingStore.getState().getCurrentStep();
        const state = useOnboardingStore.getState();
        const settings = state.getSettings();
        const emailFieldId = currentStep?.fields?.find(f => f.type === 'email')?.id ?? null;
        const tipsTricksFieldId = currentStep?.fields?.find(f => f.type === 'checkbox')?.id ?? null;
        const email = settings?.find(f => f.id === emailFieldId)?.value ?? null;
        const tipsTricks = settings?.find(f => f.id === tipsTricksFieldId)?.value ?? null;
        let data = {
                step:currentStep.id,
                email:email,
                tips_tricks:tipsTricks,
            }

        await updateAction( data, 'update_email' );
        set({ isUpdating: false });
    },
    validateLicense: async ( ) => {
        set({ isUpdating: true });
        set({ licenseStatus: 'activating' });
        /*
        To use for license , code can be modified from here onward
        //const license = useOnboardingStore.getState().getValue('license');
        let data = {
            license:license,
            email:email,
            password:password,
        }
        let response = await updateAction(data, 'activate_license');*/
        const currentStep = useOnboardingStore.getState().getCurrentStep();
        const state = useOnboardingStore.getState();
        const onboardingDataObj = state.onboardingData;
        const settings = state.getSettings();
        const emailFieldId = currentStep?.fields?.find(f => f.type === 'email')?.id ?? null;
        const passwordFieldId = currentStep?.fields?.find(f => f.type === 'password')?.id ?? null;

        const email = settings?.find(f => f.id === emailFieldId)?.value ?? '';
        const password = settings?.find(f => f.id === passwordFieldId)?.value ?? '';

        let inputHasError = false;
        let message = __('Something went wrong. Please contact plugin support.', 'ONBOARDING_WIZARD_TEXT_DOMAIN');
        let isSuccess = false;

        if (!email) {
            message = __('Please enter your email address.', 'ONBOARDING_WIZARD_TEXT_DOMAIN');
            inputHasError = true;
        }

        if (!password) {
            message = __('Please enter your password.', 'ONBOARDING_WIZARD_TEXT_DOMAIN');
            inputHasError = true;
            set({ responseCode: 'BADAUTHPWD' });
        }

        if (!email && !password) {
            message = __('Please enter both email and password.', 'ONBOARDING_WIZARD_TEXT_DOMAIN');
            inputHasError = true;
        }

        if (inputHasError) {
            set({ isUpdating: false });
            set({ licenseStatus: 'failed' });
            set({ responseSuccess: isSuccess, responseMessage: message });
            return false;
        }


        let data = {
            action:'udmupdater_ajax',
            subaction: 'connect',
            nonce: onboardingDataObj.udmupdater_nonce,
            userid: onboardingDataObj.udmupdater_muid,
            slug: onboardingDataObj.udmupdater_slug,
            email: email,
            password: password,
        }

        // Add other fields from the current step's settings dynamically
        if (currentStep?.fields) {
            currentStep.fields.forEach(field => {
                // Skip email and password as they are handled explicitly
                if (field.id !== emailFieldId && field.id !== passwordFieldId) {
                    const fieldValue = settings?.find(s => s.id === field.id)?.value;
                    if (fieldValue !== undefined && fieldValue !== false) {
                        data[field.id] = fieldValue;
                    }
                }
            });
        }

        let resp = await adminAjaxRequest(data);
        try {
            if (resp.hasOwnProperty('code')) {
                console.log('Code: '+resp.code);
                set({ responseCode: resp.code });
                if (resp.code == 'INVALID') {
                    message = __('The response from the remote site could not be decoded. (More information is recorded in the browser console).', 'ONBOARDING_WIZARD_TEXT_DOMAIN');
                } else if (resp.code == 'BADAUTH') {
                    if (resp.hasOwnProperty('data')) {
                        message = resp.msg;
                        if(resp.data === 'invalidpassword'){
                            set({ responseCode: 'BADAUTHPWD' }); //custom response code for password error
                            message = __('Oops, invalid password.', 'ONBOARDING_WIZARD_TEXT_DOMAIN');
                        }
                    } else {
                        message = __('Your email address and password were not recognised.', 'ONBOARDING_WIZARD_TEXT_DOMAIN');
                    }
                } else if (resp.code == 'OK') {
                    //if (resp.hasOwnProperty('was_previously_sharing_licence')) {
                        //In future we should add content here as per simba vendor package.
                    //}
                    //message = __('You have successfully connected for access to updates to this plugin.', 'ONBOARDING_WIZARD_TEXT_DOMAIN');
                    if (resp.hasOwnProperty('tfa_enabled')) {
                        set((state) => {                           
                            currentStep.fields = currentStep.fields.map(item => ({
                                ...item,
                                visible_if: {
                                    field: 'is_tfa',
                                    equals: false,
                                }
                            }));

                            currentStep.fields = [
                                ...currentStep.fields,
                                {
                                    id: 'tfa',
                                    type: 'text',
                                    visible_if: {
                                        field: 'is_tfa',
                                        equals: true,
                                    },
                                },
                                {
                                    id: 'is_tfa',
                                    type: 'hidden',
                                    default: true,
                                },
                            ];

                            const fn = (window as any).pluginOnboardingActions?.beforeTfa;
                            if (fn) {
                                const extra = fn(currentStep, resp);
                                
                                currentStep.title = extra?.title ?? currentStep.title;
                                currentStep.subtitle = extra?.subtitle ?? currentStep.subtitle;
                                currentStep.title_conditional.isUpdating = extra?.isUpdating ?? currentStep.title_conditional.isUpdating;
                                currentStep.icon = extra?.icon ?? currentStep.icon;
                            }

                            return {
                                currentStep,
                                licenseStatus: 'none',
                                isUpdating: false
                            };
                        });

                        return false;
                    } else {
                        // Check extra services
                        const fn = (window as any).pluginOnboardingActions?.updateLicenseBullet;
                        if (fn) {
                            let hasError = false;
                            let errorMessage = '';

                            const requests = Object.keys(data).map(key => {
                                return Promise.resolve(fn(key, data, currentStep, settings, state.setValue))
                                    .then((external) => {
                                        // If external is string => error
                                        if (typeof external === 'string') {
                                            hasError = true;
                                            errorMessage = external;
                                            return undefined;
                                        }

                                        // If external is normal object
                                        if (external && typeof external === 'object') {
                                            return {
                                                icon: external.icon,
                                                text: external.text,
                                            };
                                        }

                                        return undefined;
                                    })
                                    .catch(() => undefined);
                            });

                            const results = (await Promise.all(requests))
                                .filter(Boolean);

                            // If error, stop here
                            if (hasError) {
                                message = errorMessage;
                                isSuccess = false;
                            } else if (results.length > 0) {
                                set((state) => {                           
                                    currentStep.bullets = results;
                                    currentStep.icon = 'license';

                                    return { 
                                        currentStep,
                                    };
                                });
                            }
                        }

                        message = '';
                        isSuccess = true;
                        set({ licenseStatus: 'activated' });
                    }
                } else if (resp.code == 'ERR') {
                    message = __('Your login was accepted, but no available entitlement for this plugin was found.', 'ONBOARDING_WIZARD_TEXT_DOMAIN')+' '+__('Has your licence expired, or have you used all your available licences elsewhere?', 'ONBOARDING_WIZARD_TEXT_DOMAIN');
                }
            } else {
                message = __('The response from the remote site could not be decoded. (More information is recorded in the browser console).', 'ONBOARDING_WIZARD_TEXT_DOMAIN');
                console.log('No response code found');
                console.log(resp);
            }
        } catch (e) {
            message = __('The response from the remote site could not be decoded. (More information is recorded in the browser console).', 'ONBOARDING_WIZARD_TEXT_DOMAIN');
            console.log(e);
            console.log(resp);
        }

        set({ isUpdating: false });

        // Always populate response flags so UI can show success/failure message
        set({ responseSuccess: isSuccess, responseMessage: message});

        if(!isSuccess) {
            set({ licenseStatus: 'failed' });
        }

        // Return a structured result to the caller
        return isSuccess;
    },
    getPluginsStep: () => {
        const state = useOnboardingStore.getState();
        return state.steps.find(step =>
            Array.isArray(step.fields) &&
            step.fields.some(field => field.id === 'plugins')
        );
    },
    updatePluginAction: (pluginId, nextAction) => {
        set(state => {
            const pluginStep = state.getPluginsStep();
            if (!pluginStep) return {};

            const updatedSteps = state.steps.map(step => {
                if (step.id !== pluginStep.id) return step;

                return {
                    ...step,
                    fields: step.fields.map(field => {
                        if (field.id !== "plugins") return field;

                        return {
                            ...field,
                            options: field.options.map(opt =>
                                opt.id === pluginId
                                    ? { ...opt, action: nextAction }
                                    : opt
                            )
                        };
                    })
                };
            });

            return { steps: updatedSteps };
        });
    },
    installPlugins: async () => {
        set({isInstalling: true});
        const state = useOnboardingStore.getState();

        // Find the plugin step independent of the current step
        const pluginStep = state.getPluginsStep();
        const pluginData = pluginStep?.fields?.find(f => f.id === 'plugins');

        // Plugin list user selected:
        const plugins = state.getValue('plugins');

        // Loop through the plugins and install each one
        for (const plugin of plugins) {
            //from pluginData, get the field with the same id as the plugin and retrieve the action
            const field = pluginData?.options.find((field) => field.id === plugin);
            let next_action = field?.action || 'download';
            let previous_action = null;
            while (true) {
                if (
                    next_action === 'installed' ||
                    next_action === 'upgrade-to-pro' ||
                    next_action === undefined ||
                    next_action === previous_action
                ) {
                    set({ footerMessage: "" });
                    break;
                }
                set({ footerMessage: getActionNiceName(next_action, plugin) });
                let data = {
                    plugin: plugin
                }

                let response = await updateAction(data, next_action);
                previous_action = next_action;
                next_action = response?.data?.next_action;

                await state.updatePluginAction(plugin, next_action);

            }
        }

        set({isInstalling: false});
    },

    setSteps: (steps) => set({ steps }),

    getConditionalText: (
        step,
        key,
        {
            isUpdating = false,
            isInstalling = false,
            responseMessage = "",
            responseSuccess = false,
            licenseStatus = 'none',
        } = {},
        fallback = ""
    ) => {
        const mapKey = (key + "_conditional") as
            | "title_conditional"
            | "subtitle_conditional";
        const cond = step[mapKey];

        if (!cond) return fallback;

        // license flow conditions
        if (
            step.type === "license" &&
            (isUpdating || (responseMessage !== "" && responseSuccess) || licenseStatus !== "none")
        ) {
            if (licenseStatus == 'activated' && cond.hasOwnProperty("licenseActivated")) {
                return cond.licenseActivated;
            }
            if (isUpdating && cond.hasOwnProperty("isUpdating")) {
                return cond.isUpdating;
            }
        }

        if (isInstalling && cond.hasOwnProperty("isInstalling")) {
            return cond.isInstalling;
        }

        return fallback;
    },
    areAllInstalled: (options: OptionLike[]) => {
        if (!Array.isArray(options) || options.length === 0) return false;
        return options.every(
            (option) =>
                option?.action === 'installed' || option?.action === 'upgrade-to-pro'
        );
    },

}));

export default useOnboardingStore;