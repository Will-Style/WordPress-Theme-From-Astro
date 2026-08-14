import { useState, useEffect } from "react";
import TextInput from "./TextInput";
import FieldWrapper from "./FieldWrapper";
import { __ } from "@wordpress/i18n";
import { isValidEmail } from "@/utils/validators";

const Email = ({ field, onChange, value }) => {
    const [isValid, setIsValid] = useState(false); // null = untouched, true = valid, false = invalid
    const [touched, setTouched] = useState(false);

    // Reset validation when value becomes empty
    useEffect(() => {
        if (!value) {
            setIsValid(false);
            setTouched(false);
            return;
        }

        // Only pre-validate if value is non-empty AND field just loaded (like a default value)
        if (value && !touched) {
            setIsValid(isValidEmail(value));
            setTouched(true);
        }
    }, [value]);

    const handleBlur = () => {
        setTouched(true);
        if (value) {
            setIsValid(isValidEmail(value));
        } else {
            setIsValid(false);
        }
    };

    return (
        <FieldWrapper inputId={field.id} label={field.label}>
            <div className="relative w-full">
                <TextInput
                    id={field.id}
                    placeholder={__("Enter your e-mail address", "ONBOARDING_WIZARD_TEXT_DOMAIN")}
                    type="email"
                    field={field}
                    onChange={(e) => onChange(e.target.value)}
                    onBlur={handleBlur}
                    value={value}
                    className={`w-full border rounded-md px-3 py-2 focus:outline-none focus:ring-0 
                        ${touched && isValid === true ? "border-[#046C4E]" : ""}
                        ${touched && isValid === false ? "border-red" : ""}`}
                />

                {touched && isValid === true && value && (
                    <span className="absolute right-3 top-1/2 -translate-y-1/2 text-[#046C4E]">✔</span>
                )}
                {touched && isValid === false && value && (
                    <span className="absolute right-3 top-1/2 -translate-y-1/2 text-red">✖</span>
                )}
            </div>
        </FieldWrapper>
    );
};

export default Email;
