import {memo} from "@wordpress/element";
import * as Switch from '@radix-ui/react-switch';
import { isRTL } from '@wordpress/i18n';
// @ts-ignore
import Icon from '@/utils/Icon';


interface SwitchInputProps {
  label: string;
  value: boolean;
  id: string;
  onChange: (value: boolean) => void;
  required?: boolean;
  disabled?: boolean;
}

const SwitchInput = ({
    label,
    value,
    id,
    onChange,
    disabled
}:SwitchInputProps) => {

    const rtl  = isRTL();

    const thumbClassRTLPart = rtl
        ? "translate-x-[-15px] data-[state=checked]:translate-x-[0px]"
        : "translate-x-[0px] data-[state=checked]:translate-x-[15px]";

    return (
        <Switch.Root
            className={`w-[35px] h-[19px] px-1 flex flex-row justify-between items-center flex-shrink-0 rounded-full relative data-[state=checked]:bg-[var(--teamupdraft-orange-dark)] bg-gray-500 ${disabled ? "opacity-50" : ""}`}
            id={id}
            checked={!!value}
            aria-label={label}
            disabled={disabled}
            onCheckedChange={onChange}
        >
            <Switch.Thumb
                className={`block w-[13px] h-[13px] bg-white rounded-full shadow-lg ring-0 transition-transform duration-200 ease-in-out ${thumbClassRTLPart}`}
            />
            {disabled && (
                <span className="inset-0 flex items-center justify-center">
                    <Icon name="lock" fill="black" color="black" size={12}/>
                </span>
            )}
        </Switch.Root>
    );
};

export default memo(SwitchInput);