"use client";

import { memo, useState, useEffect, useRef } from '@wordpress/element';
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

interface MultiSelectOption {
    value: string;
    label: string;
    icon?: string;
}

interface MultiSelectFieldProps {
    field: {
        id: string;
        label?: string;
        options: MultiSelectOption[];
        is_lock?: boolean;
        tooltip?: any;
        placeholder?: string;
        [key: string]: any;
    };
    value: string[];
    onChange: (value: string[]) => void;
    fieldStatus?: (id: string, success: boolean) => void;
}

const MultiSelect = ({ field, value, onChange, fieldStatus } : MultiSelectFieldProps ) => {
    const [open, setOpen] = useState(false);
    const selectedValues = Array.isArray(value) ? value : [];
    const removeButtonRefs = useRef<Record<string, HTMLSpanElement | null>>({});

    const selectedOptions = selectedValues
        .map(val => field.options.find(opt => opt.value === val))
        .filter((opt): opt is MultiSelectOption => opt !== undefined);

    useEffect(() => {
        if (fieldStatus) {
            const isValid = selectedValues.length > 0;
            fieldStatus(field.id, isValid);
        }
    }, [selectedValues, field.id, fieldStatus]);

    const handleValueChange = (newValue: string) => {
        const isSelected = selectedValues.includes(newValue);
        let updatedValues;

        if (isSelected) {
            updatedValues = selectedValues.filter((item) => item !== newValue);
        } else {
            updatedValues = [...selectedValues, newValue];
        }
        onChange(updatedValues);
    };

    const handleRemoveTag = (e: React.MouseEvent<HTMLSpanElement> | React.KeyboardEvent<HTMLSpanElement>, itemToRemove: string) => {
        e.stopPropagation();
        e.preventDefault();
        const updatedValues = selectedValues.filter((item) => item !== itemToRemove);
        onChange(updatedValues);
    };

    const handleSelectOpenChange = (newOpen: boolean) => {
        const clickedRemoveButton = Object.values(removeButtonRefs.current).some(
            (ref) => ref && ref.contains(event.target as Node)
        );

        if (clickedRemoveButton) {
            return;
        }
        setOpen(newOpen);
    };

    return (
        <FieldWrapper
            inputId={field.id}
            label={field.label}
            tooltip={field.tooltip}
        >
            <Select onValueChange={handleValueChange} value="" open={open} onOpenChange={handleSelectOpenChange} disabled={!!field.is_lock}>
                <SelectTrigger className="flex w-full items-center border border-gray-400 rounded-md min-h-[40px] flex-wrap p-2 pr-8">
                    <div className="flex flex-wrap flex-1 items-center gap-1">
                        {selectedOptions.map((opt) => (
                            <div
                                key={opt.value}
                                className="flex items-center bg-gray-200 rounded-md px-2 py-1 text-sm"
                            >
                                {opt?.icon && (
                                        <Icon
                                            name={opt.icon}
                                            size={16}
                                            color="gray-700"
                                            className="mr-1"
                                        />
                                    )}
                                {opt.label}
                                <span
                                    ref={(el) => (removeButtonRefs.current[opt.value] = el)}
                                    role="button"
                                    tabIndex={0}
                                    onClick={(e) => handleRemoveTag(e, opt.value)}
                                    onKeyDown={(e) => {
                                        if (e.key === 'Enter' || e.key === ' ') {
                                            handleRemoveTag(e, opt.value);
                                        }
                                    }}
                                    className="ml-2 p-1 text-gray-600 hover:text-gray-900 cursor-pointer"
                                >
                                    <Icon name="times" size={18} color="gray-600" />
                                </span>
                            </div>
                        ))}
                        {selectedValues.length === 0 && (
                            <SelectValue placeholder={field.placeholder || __("Select options...", "ONBOARDING_WIZARD_TEXT_DOMAIN")} className="flex-1" />
                        )}
                        {selectedValues.length > 0 && (
                            <span className="flex-1 min-w-[50px] h-full" />
                        )}
                    </div>
                </SelectTrigger>
                <SelectContent>
                    {field.options.map((option) => {
                        const isSelected = selectedValues.includes(option.value);
                        return (
                            <SelectItem key={option.value} value={option.value}>
                                <div className="flex items-center w-full gap-2">
                                    {option.icon && (
                                        <Icon
                                            name={option.icon}
                                            size={16}
                                            color="gray-700"
                                        />
                                    )}
                                    <span>{option.label}</span>
                                </div>
                                {isSelected && (
                                    <span className="absolute right-2 top-1/2 -translate-y-1/2 flex h-3.5 w-3.5 items-center justify-center">
                                        <Icon name="CheckRoundedIcon" size={16} color="blue" />
                                    </span>
                                )}
                            </SelectItem>
                        );
                    })}
                </SelectContent>
            </Select>
        </FieldWrapper>
    );
};

export default memo(MultiSelect);