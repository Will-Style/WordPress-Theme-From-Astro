import { memo } from 'react';
import FieldWrapper from './FieldWrapper';
import { __ } from '@wordpress/i18n';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from './DropdownInput';
import Icon from '../../utils/Icon';
import useOnboardingStore from '../../store/useOnboardingStore'; // Import the store to access onboarding data

/**
 * Interface for a single dropdown option.
 */
interface DropdownOption {
    value: string;
    label: string;
    icon?: string;
    is_premium?: boolean; // Property to indicate if the option is premium.
}

/**
 * Props for the DropdownInput component.
 */
interface DropdownFieldProps {
    field: {
        id: string;
        label?: string;
        options: DropdownOption[];
        is_lock?: boolean;
        tooltip?: any;
        [key: string]: any;
    };
    value: string;
    onChange: (value: string) => void;
}

/**
 * DropdownInput component for selecting a single option from a list.
 * Supports displaying premium labels and disabling options for non-pro users.
 */
const DropdownInput = ({ field, value, onChange } :DropdownFieldProps) => {
    // Access onboarding data from the store to check if the user is a Pro user.
    const { onboardingData } = useOnboardingStore();
    const isProUser = onboardingData.is_pro;

    // Compute selected option once and normalize icon to a string so Icon always receives a string.
    const selectedOption = field.options.find(opt => opt.value === value);
    const selectedIcon = selectedOption?.icon ?? '';
    const selectedLabel = selectedOption?.label ?? '';

    return (
        <FieldWrapper
            inputId={field.id}
            label={field.label}
            tooltip={field.tooltip}
        >
            <Select onValueChange={onChange} value={value} disabled={!!field.is_lock}>
                <SelectTrigger className="w-full">
                    <SelectValue placeholder={__("Select an option", "ONBOARDING_WIZARD_TEXT_DOMAIN")}>
                        {/* Display selected option's icon and label */}
                        {value ? (
                            <span className="flex items-center gap-2">
                                {selectedIcon && (
                                    <Icon
                                        name={selectedIcon}
                                        size={16}
                                        color="gray-700"
                                    />
                                )}
                                <span>{selectedLabel}</span>
                            </span>
                        ) : (
                            __("Select an option", "ONBOARDING_WIZARD_TEXT_DOMAIN")
                        )}
                    </SelectValue>
                </SelectTrigger>
                <SelectContent>
                    {field.options.map((option) => {
                        const isPremium = option.is_premium;
                        const isDisabled = !!(isPremium && !isProUser);

                        return (
                            <SelectItem
                                key={option.value}
                                value={option.value}
                                disabled={isDisabled}
                                premiumLabel={isPremium && !isProUser ? __('Premium', 'ONBOARDING_WIZARD_TEXT_DOMAIN') : null} // Pass premium label here
                            >
                                {/* This content goes into SelectPrimitive.ItemText */}
                                <span className="flex items-center gap-2">
                                    {option.icon && (
                                        <Icon
                                            name={option.icon}
                                            size={16}
                                            color="gray-700"
                                        />
                                    )}
                                    <span>{option.label}</span>
                                </span>
                            </SelectItem>
                        );
                    })}
                </SelectContent>
            </Select>
        </FieldWrapper>
    );
};

export default memo(DropdownInput);