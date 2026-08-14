import {memo} from "@wordpress/element";
import Icon from "../../utils/Icon";
import * as Checkbox from '@radix-ui/react-checkbox';

interface CheckboxInputProps {
  label: string;
  value: boolean;
  id: string;
  onChange: (value: boolean) => void;
  required?: boolean;
  disabled?: boolean;
}

const CheckboxInput = ({
    label,
    value,
    id,
    onChange,
    disabled,
}:CheckboxInputProps) => {
    return (
        <Checkbox.Root
            className={`flex-shrink-0 ${disabled ? "opacity-50 pointer-events-none" : ""}`}
            id={id}
            checked={!!value}
            aria-label={label}
            disabled={disabled}
            onCheckedChange={onChange}
        >
            {!value && <Icon
                name="checkbox-rounded-outline"
                fill="#B7BABD"
                size={25}
                strokeWidth={3}
            />}
            <Checkbox.Indicator className="flex items-center justify-center">
                <Icon
                    name="checkbox-rounded"
                    size={25}
                    strokeWidth={3}
                />
            </Checkbox.Indicator>
        </Checkbox.Root>
    );
};

export default memo(CheckboxInput);