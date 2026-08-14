import { memo, TextareaHTMLAttributes } from 'react';
import FieldWrapper from './FieldWrapper';
import { clsx } from 'clsx';

interface TextareaProps extends Omit<TextareaHTMLAttributes<HTMLTextAreaElement>, 'onChange' | 'value'> {
    field: {
        id: string;
        label?: string;
        placeholder?: string;
        is_lock?: boolean;
        tooltip?: any;
        context?: any;
        context_html?: string;
        rows?: number;
        [key: string]: any;
    };
    value: string;
    onChange: (value: string) => void;
    fieldStatus?: (id: string, success: boolean) => void;
}

const Textarea = ({ field, value, onChange, fieldStatus, ...props }: TextareaProps) => {
    const disabled = field.is_lock === true;
    const rows = field.rows ?? 4; // Default to 4 rows if not specified

    return (
        <FieldWrapper
            inputId={field.id}
            label={field.label}
            tooltip={field.tooltip}
            context={field.context}
            contextHtml={field.context_html ?? ''}
        >
            <textarea
                id={field.id}
                placeholder={field.placeholder}
                value={value}
                onChange={(e) => onChange(e.target.value)}
                disabled={disabled}
                rows={rows}
                className={clsx(
                    "w-full rounded-md border border-gray-400 p-2 focus:border-primary-dark focus:outline-none focus:ring disabled:cursor-not-allowed disabled:border-gray-200 disabled:bg-gray-200",
                    disabled && "bg-gray-200 border-gray-200"
                )}
                {...props}
            />
        </FieldWrapper>
    );
};

export default memo(Textarea);