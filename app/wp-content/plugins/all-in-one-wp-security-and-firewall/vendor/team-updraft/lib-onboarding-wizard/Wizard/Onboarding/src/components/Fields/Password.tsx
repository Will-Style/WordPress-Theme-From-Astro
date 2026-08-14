import TextInput from "./TextInput";
import FieldWrapper from "./FieldWrapper";
import {InputHTMLAttributes, useState} from "react";
import {__} from "@wordpress/i18n";
// @ts-ignore
import {get_website_url} from "@/utils/lib.js";
// @ts-ignore
import useOnboardingStore from "@/store/useOnboardingStore";
import Icon from "../../utils/Icon";
import { SettingField } from "../../types"; // Import SettingField

interface PasswordProps extends Omit<InputHTMLAttributes<HTMLInputElement>, 'onChange' | 'type'> {
    field: SettingField;
    onChange: (value: string) => void;
    value: string;
    fieldStatus?: (id: string, success: boolean) => void;
}

const Password = ({
                   field,
                   onChange,
                   value,
                   fieldStatus,
                   ...props
               }: PasswordProps) => {
    const [showPassword, setShowPassword] = useState(false);
    const {
        onboardingData,
        responseCode,
        responseMessage,
    } = useOnboardingStore();

    // Check field.show_forgot_password, defaulting to true if undefined
    const showForgotPassword = field.show_forgot_password !== undefined ? field.show_forgot_password : true;

    const forgot_password = get_website_url(onboardingData.forgot_password_url, {
        utm_source: onboardingData.prefix + '_onboarding',
        utm_content: 'forgot-password'
    });

    const handleChange = (e) => {
        // Clear auth error on new input
        if (responseCode === "BADAUTHPWD") {
            useOnboardingStore.setState({
                responseCode: null,
                responseMessage: null
            });
        }

        onChange(e.target.value);
    };

    const isBadAuthError = responseCode === "BADAUTHPWD" && responseMessage;

    return (
        <>
            <FieldWrapper
                inputId={field.id}
                label={field.label}
                context={field.context}
                {...(field.context_html && { contextHtml: field.context_html })}
            >
                <div className="relative w-full group">
                    <TextInput
                        id={field.id}
                        placeholder={__("Enter your password", "ONBOARDING_WIZARD_TEXT_DOMAIN")}
                        type={showPassword ? "text" : "password"}
                        onChange={handleChange}
                        value={value}
                        className={
                            isBadAuthError
                                ? "border border-[#B40000] bg-[#B40000]/10 pr-10"
                                : "pr-10"
                        }
                        {...props}
                    />

                    {/* Toggle Eye Icon */}
                    <button
                        type="button"
                        className="absolute right-3 top-1/2 -translate-y-1/2 focus:outline-none opacity-0 group-hover:opacity-100 group-focus-within:opacity-100
                 transition-opacity duration-200"
                        onClick={() => setShowPassword((prev) => !prev)}
                    >
                        <Icon
                            name={showPassword ? "eye" : "eye-off"}
                            size={18}
                            color="#888D91"
                            fill="#888D91"
                        />
                    </button>
                </div>
                {showForgotPassword && ( // Conditional rendering for the block

                    <div className="flex flex-row-reverse items-start gap-3 mt-1">
                        <a className="underline text-[var(--teamupdraft-grey-600)] focus:outline-none focus:ring-0 w-auto" target="_blank" href={forgot_password}>
                            {__("Forgot your password?", "ONBOARDING_WIZARD_TEXT_DOMAIN") }
                        </a>
                        {isBadAuthError && (
                            <div className="flex items-start gap-2 text-sm flex-1">
                                <Icon
                                    name='warning'
                                    color='#B40000'
                                    fill='#fff'
                                    stroke='#B40000'
                                    size={16}
                                    className="ml-2"
                                />
                                <p className="text-[#B40000] break-words whitespace-normal">{responseMessage}</p>
                            </div>
                        )}
                    </div>
                )}
            </FieldWrapper>
        </>
    )
};

export default Password;