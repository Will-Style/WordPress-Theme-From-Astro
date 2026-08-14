import {
    memo, useState, useEffect,
    type ChangeEvent,
    type KeyboardEvent,
    type ClipboardEvent
} from 'react';
import FieldWrapper from './FieldWrapper';
import TextInput from './TextInput';
import Icon from '../../utils/Icon';
import { __ } from '@wordpress/i18n';

interface NumberInputWithControlsProps {
    field: {
        id: string;
        label?: string;
        default?: number;
        min?: number;
        max?: number;
        is_lock?: boolean;
        tooltip?: any;
        [key: string]: any;
    };
    value: number;
    onChange: (value: number) => void;
}

const NumberInputWithControls = ({ field, value, onChange }: NumberInputWithControlsProps) => {
    const min = field.min ?? 0;
    const max = field.max ?? Infinity;
    const disabled = field.is_lock === true;

    const [internalValue, setInternalValue] = useState<number>(value);

    useEffect(() => {
        setInternalValue(value);
    }, [value]);

    const handleInputChange = (e: ChangeEvent<HTMLInputElement>) => {
        const sanitizedValue = e.target.value.replace(/[^0-9]/g, '');
        const newValue = sanitizedValue === '' ? min : parseInt(sanitizedValue, 10);
        
        const clampedValue = Math.max(min, Math.min(max, newValue));
        setInternalValue(clampedValue);
        onChange(clampedValue);
    };

    const handleKeyDown = (e: KeyboardEvent<HTMLInputElement>) => {
        if (
            ['Backspace', 'Delete', 'Tab', 'Escape', 'Enter', 'ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(e.key) ||
            ((e.ctrlKey || e.metaKey) && ['a', 'c', 'v', 'x'].includes(e.key.toLowerCase()))
        ) {
            return;
        }
        if (!/^\d$/.test(e.key)) {
            e.preventDefault();
        }
    };

    const handlePaste = (e: ClipboardEvent<HTMLInputElement>) => {
        e.preventDefault();
        if (disabled) return;

        const pasteData = e.clipboardData.getData('text');
        const sanitizedPasteData = pasteData.replace(/[^0-9]/g, '');
        
        const newValueString = sanitizedPasteData;
        
        const newValue = newValueString === '' ? min : parseInt(newValueString, 10);
        const clampedValue = Math.max(min, Math.min(max, newValue));

        setInternalValue(clampedValue);
        onChange(clampedValue);

        // After pasting, set the cursor to the end of the new value.
        setTimeout(() => {
            const currentTarget = e.target as HTMLInputElement;
            currentTarget.value = String(clampedValue);
            currentTarget.setSelectionRange(currentTarget.value.length, currentTarget.value.length);
        }, 0);
    };

    const handleIncrement = () => {
        if (!disabled && internalValue < max) {
            const newValue = internalValue + 1;
            setInternalValue(newValue);
            onChange(newValue);
        }
    };

    const handleDecrement = () => {
        if (!disabled && internalValue > min) {
            const newValue = internalValue - 1;
            setInternalValue(newValue);
            onChange(newValue);
        }
    };

    return (
        <FieldWrapper
            inputId={field.id}
            label={field.label}
            tooltip={field.tooltip}
        >
            <div className="flex w-full items-center border border-gray-400 rounded-md overflow-hidden">
                <TextInput
                    id={field.id}
                    type="number"
                    value={internalValue}
                    onChange={handleInputChange}
                    onKeyDown={handleKeyDown}
                    onPaste={handlePaste}
                    min={min}
                    max={max}
                    disabled={disabled}
                    className="text-center flex-1 border-none focus:ring-0 focus:border-none rounded-none bg-white"
                />
                <div className="flex h-full divide-x divide-gray-400">
                    <button
                        type="button"
                        onClick={handleDecrement}
                        disabled={disabled || internalValue <= min}
                        className="p-2 bg-gray-100 hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <Icon name="minus" size={16} color="gray-700" />
                    </button>
                    <button
                        type="button"
                        onClick={handleIncrement}
                        disabled={disabled || internalValue >= max}
                        className="p-2 bg-gray-100 hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <Icon name="plus" size={16} color="gray-700" />
                    </button>
                </div>
            </div>
        </FieldWrapper>
    );
};

export default memo(NumberInputWithControls);