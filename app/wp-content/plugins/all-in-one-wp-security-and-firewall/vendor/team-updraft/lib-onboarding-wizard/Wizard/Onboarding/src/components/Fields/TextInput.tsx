import { forwardRef, InputHTMLAttributes, useState, useEffect } from "react";
import { twMerge } from "tailwind-merge";

interface TextInputProps extends InputHTMLAttributes<HTMLInputElement> {
    type?: string;
    storedValue?: string;
}

const TextInput = forwardRef<HTMLInputElement, TextInputProps>(
    ({ type = "text", className, value, defaultValue, onChange, ...props }, ref) => {
        const [internalValue, setInternalValue] = useState(value ?? defaultValue ?? "");

        // Sync when `value` changes (controlled mode)
        useEffect(() => {
            if (value !== undefined) {
                setInternalValue(value);
            }
        }, [value]);

        const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
            const newVal = e.target.value;
            setInternalValue(newVal);
            onChange?.(e); // Pass event to parent (so Email, Password, etc. still work)
        };

        return (
            <input
                ref={ref}
                type={type}
                value={internalValue}
                onChange={handleChange}
                className={twMerge(
                    "w-full rounded-md border border-gray-400 p-2 focus:border-primary-dark focus:outline-none focus:ring disabled:cursor-not-allowed disabled:border-gray-200 disabled:bg-gray-200",
                    className
                )}
                {...props}
            />
        );
    }
);

TextInput.displayName = "TextInput";

export default TextInput;
