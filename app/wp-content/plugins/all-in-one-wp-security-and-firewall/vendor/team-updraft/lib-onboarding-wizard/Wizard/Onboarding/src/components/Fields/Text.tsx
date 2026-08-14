import { memo } from 'react';
import FieldWrapper from './FieldWrapper';
import TextInput from './TextInput';
import { clsx } from 'clsx';

interface TextFieldProps {
    field: {
        id: string;
        label?: string;
        placeholder?: string;
        is_lock?: boolean;
        tooltip?: any;
        context?: any;
        context_html?: string;
        prefix?: string;
        [key: string]: any;
    };
    value: string;
    onChange: (value: string) => void;
}

const Text = ({ field, value, onChange }: TextFieldProps) => {
    const disabled = field.is_lock === true;

    return (
        <FieldWrapper
            inputId={field.id}
            label={field.label}
            tooltip={field.tooltip}
            context={field.context}
            contextHtml={field.context_html ?? ''}
        >
            <div className={clsx(
                "flex items-center w-full rounded-md border border-gray-400 focus-within:border-primary-dark focus-within:ring focus-within:ring-primary/20",
                disabled && "bg-gray-200 border-gray-200"
            )}>
                {field.prefix && (
                    <span className="flex-shrink-0 bg-gray-100 text-gray-700 px-3 py-2 rounded-l-md border-r border-gray-400">
                        {field.prefix}
                    </span>
                )}
                <TextInput
                    id={field.id}
                    type="text"
                    placeholder={field.placeholder}
                    value={value}
                    onChange={(e) => onChange(e.target.value)}
                    disabled={disabled}
                    className={clsx(
                        "flex-1 border-none focus:ring-0 focus:border-none rounded-none",
                        field.prefix ? "rounded-r-md" : "rounded-md"
                    )}
                />
            </div>
        </FieldWrapper>
    );
};

export default memo(Text);