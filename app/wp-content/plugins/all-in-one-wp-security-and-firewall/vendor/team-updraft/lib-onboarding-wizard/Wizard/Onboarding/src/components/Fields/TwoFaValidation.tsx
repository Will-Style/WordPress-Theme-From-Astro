import TextInput from "./TextInput";
import FieldWrapper from "./FieldWrapper";
import Icon from "../../utils/Icon";
import useOnboardingStore from "../../store/useOnboardingStore";
import {useEffect, useState} from "@wordpress/element";
import {handleRequest} from '../../utils/api.js';

type ValueProps = {
    key: string,
    validated: boolean
};

type TwoFaValidationProps = {
    field: {
        id: string;
        label: string;
        placeholder: string;
    };
    onChange: (newValue: ValueProps) => void,
    fieldStatus: (success: boolean) => void,
    value: ValueProps;
};

const TwoFaValidation = ({
    field,
    onChange,
    fieldStatus,
    value,
}: TwoFaValidationProps) => {
    const {
        getValue,
        onboardingData,
    } = useOnboardingStore();
    const key = value?.key || '';
    const [keyValid, setKeyValid] = useState(getValue(field.id)?.validated || false);
    const [isDisabled, setIsDisabled] = useState(keyValid);

    const handleChange = (newKey: string) => {
        newKey = newKey.replace(/[^0-9]/g, "");
        onChange({key: newKey, validated: false});
    }

    /**
     * Check if the provided key is valid by making an API request.
     * @param {string} key
     */
    const keyIsValid = async (key: string) => {
        const path = onboardingData.prefix + '/v1/onboarding/tfa_key_is_valid';
        const method = 'POST';

        const args = {
            path,
            method,
            data: { key: key, nonce: onboardingData.nonce },
        };
        const response = await handleRequest(args);
        return response.success;
    };

    useEffect(() => {
        if (keyValid) {
            return;
        }

        fieldStatus(false);

        // Disable input after 6 digits have been entered.
        if (key.length === 6) {
            setIsDisabled(true);
        } else {
            return;
        }

        const delay = setTimeout(async () => {
            const isValid = await keyIsValid(key);
            onChange({key: key, validated: isValid});
            setKeyValid(isValid);
            fieldStatus(isValid);
            setIsDisabled(isValid);
        }, 300);

        return () => clearTimeout(delay);
    }, [key]);

    return (
        <div className={" w-full"} >
            <FieldWrapper inputId={field.id} label={field.label}>
                <div className={"relative w-full"}>
                    <TextInput
                        placeholder={field.placeholder}
                        id={field.id}
                        type="text"
                        onChange={(e) => handleChange(e.target.value)}
                        value={key}
                        disabled={isDisabled}
                    />
                    <div className="absolute right-4 top-1/2 -translate-y-1/2">
                        {
                            keyValid ? (
                                <Icon name="check" color="green"/>
                            ) : (
                                <Icon name="times" color="red"/>
                            )
                        }
                    </div>
                </div>
            </FieldWrapper>
        </div>
    )
};

export default TwoFaValidation;