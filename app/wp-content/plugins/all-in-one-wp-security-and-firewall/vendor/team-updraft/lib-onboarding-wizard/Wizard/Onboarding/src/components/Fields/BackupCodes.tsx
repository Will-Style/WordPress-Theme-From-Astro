import FieldWrapper from "./FieldWrapper";
import { __ } from "@wordpress/i18n";
import Icon from "../../utils/Icon";
import Copy from "../../utils/Copy";
import {useState} from "@wordpress/element";

const BackupCodes = ({
	field,
	onChange,
	value,
}) => {
    const [copied, setCopied] = useState(false);

    const handleCopy = () => {
        Copy(field.value);
        setCopied(true);
    };
    return (
        <>
            <FieldWrapper
                inputId={field.id}
                label={field.label}
            >
				<div className="w-full text-md text-[#AD2E00] font-mono mb-2">{field.value}</div>
				<button className="relative w-full justify-center text-md border rounded-xl py-1.5 flex items-center space-x-2 shadow-sm hover:bg-orange-light">
					<input id={field.id} onClick={ () => handleCopy() } className="cursor-pointer opacity-0 absolute inset-0" value={field.value}/>
					<span className={copied ? "text-green" : ""}>{!copied ? __("Copy codes", "ONBOARDING_WIZARD_TEXT_DOMAIN") : __("Copied!", "ONBOARDING_WIZARD_TEXT_DOMAIN")}</span>
					<Icon name="copy" size={18} color="gray-800" fill="gray-800"/>
				</button>
            </FieldWrapper>
        </>
    );
};

export default BackupCodes;