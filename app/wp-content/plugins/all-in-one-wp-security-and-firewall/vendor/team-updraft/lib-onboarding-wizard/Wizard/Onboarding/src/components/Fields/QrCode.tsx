import FieldWrapper from "./FieldWrapper";
import { __ } from "@wordpress/i18n";
import { QRCodeSVG } from 'qrcode.react';
import Icon from "../../utils/Icon";
import Copy from "../../utils/Copy";
import {useState} from "@wordpress/element";
const QrCode = ({
                     field,
                     onChange,
                     value,
                 }) => {
    const [copied, setCopied] = useState(false);

    const handleCopy = () => {
        Copy(field.private_key);
        setCopied(true);
    };
    return (
        <>
            <FieldWrapper
                inputId={field.id}
                label={field.label}
            >
                <div className="flex items-center space-x-4 mt-4">
                    <QRCodeSVG value={field.value} size={150} bgColor="#f3f4f6" fgColor="#000000" />

                    <div className="flex flex-col items-center flex-1">
                        <div className="text-md font-semibold mb-2">{field.private_key}</div>
                        <button className="relative w-full justify-center text-md border rounded-xl py-1.5 flex items-center space-x-2 shadow-sm hover:bg-orange-light">
                            <input id={field.id} onClick={ () => handleCopy() } className="cursor-pointer opacity-0 absolute inset-0" value={field.private_key}/>
                            <span className={copied ? "text-green" : ""}>{!copied ? __("Copy key", "ONBOARDING_WIZARD_TEXT_DOMAIN") : __("Copied!", "ONBOARDING_WIZARD_TEXT_DOMAIN")}</span>
                            <Icon name="copy" size={18} color="gray-800" fill="gray-800"/>
                        </button>
                    </div>
                </div>
            </FieldWrapper>
        </>
    );
};

export default QrCode;