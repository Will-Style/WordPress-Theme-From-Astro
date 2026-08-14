import {useEffect} from "react";
import FieldWrapper from "@/components/Fields/FieldWrapper";
import CheckboxInput from "@/components/Fields/CheckboxInput";
import SwitchInput from "@/components/Fields/SwitchInput";
import { __ } from "@wordpress/i18n";
import {get_website_url} from "@/utils/lib.js";
import useOnboardingStore from "@/store/useOnboardingStore";
import Icon from '@/utils/Icon';
import Tooltip from "../../utils/Tooltip/Tooltip";

const Checkbox = ({
    field,
    onChange,
    value,
}) => {

    const {
        onboardingData,
    } = useOnboardingStore();

    const privacy_statement = get_website_url(onboardingData.privacy_statement_url, {
        utm_source: onboardingData.prefix + '_onboarding',
        utm_content: 'mailing-list'
    });

    const privacy_url_label = onboardingData.privacy_url_label;

    const disabled = field.is_lock === true;

    // Force value to false when disabled on mount and when disabled state changes
    useEffect(() => {
        if (disabled && value !== false) {
            onChange(false);
        }
    }, [disabled]);

    const tooltipContent = field.tooltip && (
        typeof field.tooltip.text === 'string' && field.tooltip.text.trim() !== ''
    ) ? field.tooltip : null;

    // Define the label element
    const labelElement = (
        <label htmlFor={field.id}
            className={`font-normal text-md flex items-center gap-1 ${
                disabled ? "text-[#9FA4A7]" : "text-gray-1000"
            }`}
        >
            <span className="text-gray-1000 font-normal flex">
                {field.label}
            </span>
        </label>
    );

    // Define the icon element (always rendered if tooltipContent exists)
    const iconElement = tooltipContent ? (
        <Icon
            name={tooltipContent?.icon ?? 'info'}
            color="gray500"
            fill="gray500"
            size={16}
            className="ml-[-4px]"
        />
    ) : null;

    return (
        <FieldWrapper label={''} inputId={field.id}>
            <div className="flex flex-col space-y-1">
                <div className={`flex ${field.show_privacy_link ? "items-start" : "items-center"} gap-2`}>
                    {field.subtype === 'switch'
                        ? <SwitchInput
                            label={field.label}
                            onChange={(checked) => !disabled && onChange(!!checked)}
                            value={disabled ? false : !!value}
                            id={field.id}
                            disabled={disabled}
                        />
                        : <CheckboxInput
                            label={field.label}
                            onChange={(checked) => !disabled && onChange(!!checked)}
                            value={disabled ? false : !!value}
                            id={field.id}
                            disabled={disabled}
                        />
                    }
                    {window.innerWidth > 768 && tooltipContent && disabled ? ( // If disabled and has tooltip, wrap both label and icon in Tooltip
                        <Tooltip tooltip={tooltipContent} triggerClassName="flex items-center gap-2">
                            {labelElement}
                            {iconElement}
                        </Tooltip>
                    ) : (
                        // Otherwise (not disabled, or disabled without tooltip), render label and icon separately,
                        // with tooltip only on the icon if it exists and is not disabled.
                        <>
                            {labelElement}
                            {tooltipContent && ( // Icon always rendered if tooltipContent exists
                                <Tooltip tooltip={tooltipContent}>
                                    {iconElement}
                                </Tooltip>
                            )}
                        </>
                    )}
                </div>
            </div>

            {field.show_privacy_link && (
                <div className="text-left ml-8">
                    <a rel="noopener noreferrer nofollow" className="underline text-[var(--teamupdraft-grey-600)] focus:outline-none focus:ring-0"
                       target="_blank"
                       href={privacy_statement}>{privacy_url_label}</a>
                </div>
            )}

        </FieldWrapper>
    );
};

export default Checkbox;