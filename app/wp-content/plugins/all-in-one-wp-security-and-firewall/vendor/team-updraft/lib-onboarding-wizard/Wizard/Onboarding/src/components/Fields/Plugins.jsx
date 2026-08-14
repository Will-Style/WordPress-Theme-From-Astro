import FieldWrapper from "./FieldWrapper";
import {__} from "@wordpress/i18n";
import CheckboxInput from "@/components/Fields/CheckboxInput";
import Icon from "@/utils/Icon";
import useOnboardingStore from "@/store/useOnboardingStore";
import Tooltip from "../../utils/Tooltip/Tooltip";

const Plugins = ({
    field,
    onChange,
    value,
}) => {

    const {
        areAllInstalled,
    } = useOnboardingStore();


    // Convert array of objects to Record<string, string>
    // convert value to array if not already

    // Convert an incoming value to an array if not already one
    let valueValidated = value;
    if (!Array.isArray(valueValidated)) {
        valueValidated = valueValidated === '' ? [] : [valueValidated];
    }

    const selected = Array.isArray(valueValidated) ? valueValidated : [];

    /**
     * Handles a change on an individual checkbox.
     * For boolean mode, simply toggles the value.
     * Otherwise, adds or removes the selected option.
     */
    const handleCheckboxChange = (_checked, option) => {
        const newSelected = selected.includes('' + option) || selected.includes(parseInt(option))
            ? selected.filter((item) => item !== '' + option && item !== parseInt(option))
            : [...selected, option];
        onChange(newSelected);

    };

    let options = Object.values(field.options) || [];

    const optionsState = useOnboardingStore((state) => {
        const pluginStep = state.getPluginsStep();
        const pluginField = pluginStep?.fields?.find(f => f.id === 'plugins');
        return pluginField?.options ?? [];
    });

    /**
     * Determines if an option is considered checked.
     */
    const isEnabled = (optionId) => {
        return selected.includes('' + optionId) || selected.includes(parseInt(optionId));
    };

    if (Object.keys(options).length === 0) {
        return <>{__('No options found', 'ONBOARDING_WIZARD_TEXT_DOMAIN')}</>;
    }
    const allInstalled = areAllInstalled(options ?? []);

    return (
        <FieldWrapper inputId={field.id} label={field.label}>
            {!allInstalled && (
                <div className="flex flex-col space-y-2 gap-1">
                    {optionsState.map((option) =>
                        {
                            const key = option.id || option.value;
                            const label = option.title || key;
                            const isInstalled = option.action === 'installed' || option.action === 'upgrade-to-pro' ;
                            return (
                                <div key={key}>
                                    <div
                                        className="flex items-center"
                                    >
                                        <CheckboxInput
                                            label={label}
                                            onChange={(checked) => handleCheckboxChange(!!checked, key)}
                                            value={isEnabled(key)}
                                            id={`${field.id}_${key}`}
                                            disabled={isInstalled}
                                        />
                                        <label className={`ml-2 flex ${isInstalled ? 'cursor-not-allowed' : ''}`} htmlFor={`${field.id}_${key}`}>
                                            <div className="text-md font-medium text-black mr-2">{}{label}</div>
                                        </label>
                                        {isInstalled
                                            ? <Tooltip tooltip={{
                                                text: __('Already installed', 'ONBOARDING_WIZARD_TEXT_DOMAIN')
                                            }}>
                                                <Icon
                                                    name="circle-check"
                                                    color="green"
                                                    size="18"
                                                    strokeWidth={2.5}
                                                />
                                            </Tooltip>
                                            : <Icon
                                                name="link-arrow"
                                                fill="#888D91"
                                                size="12"
                                                onClick={() => {
                                                    window.open("https://wordpress.org/plugins/"+option['slug'], "_blank", "noopener,noreferrer");
                                                }}
                                            />
                                        }
                                    </div>
                                    {option.description && (
                                        <div className="text-md text-[var(--teamupdraft-grey-600)] ml-8">
                                            {option.description}
                                        </div>
                                    )}
                                </div>
                            )
                        }
                    )}
                </div>
            )}
        </FieldWrapper>
    );
};

export default Plugins;