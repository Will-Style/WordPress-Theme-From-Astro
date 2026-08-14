import License from './License';
import Checkbox from './Checkbox';
import TrackingTest from './TrackingTest';
import Email from './Email';
import Password from './Password';
import Plugins from './Plugins';
import QrCode from './QrCode';
import TwoFaValidation from './TwoFaValidation';
import BackupCodes from './BackupCodes';
import Dropdown from './Dropdown';
import NumberInputWithControls from './NumberInputWithControls';
import MultiSelectDropdown from './MultiSelectDropdown';
import Text from './Text';
import Textarea from './Textarea';
import Pricing from './Pricing';
import Status from './Status';
import { ErrorBoundary } from '../ErrorBoundary';
// @ts-ignore
import useOnboardingStore from "@/store/useOnboardingStore";
// @ts-ignore
import useAlertStore from "@/store/useAlertStore";
import ButtonInput from '../Inputs/ButtonInput';
import Alert from '../Alert';
import { normalizeValue } from '../../utils/normalizeValue';
import { useEffect } from '@wordpress/element';

type FieldsProps = {
    fields: any[];
    onChange: (id: string, value: any) => void;
    fieldStatus?: (id: string, success: boolean) => void;
};
/**
 * Fields component that renders different field types based on the field configuration
 * @param {Object} props Component props
 * @param {Array} props.fields Array of field configurations
 * @param {Function} props.onChange Callback function when field values change
 * @param {Function} props.fieldStatus Callback function when success status of field changes
 * @returns {JSX.Element|null} The rendered fields or null if no fields
 */
const Fields = ({ fields, onChange, fieldStatus = () => {} }:FieldsProps ) => {
    const {
        getValue,
        setValue,
        isEdited,
        settings,
    } = useOnboardingStore();

    const { getAlertState, setAlertState } = useAlertStore();

    // Effect to set default values for fields that haven't been edited.
    // This runs after the component renders, avoiding the "cannot update during render" warning.
    useEffect(() => {
        fields.forEach((field) => {
            const isEditedField = isEdited(field.id);
            const value = getValue(field.id);

            if (!isEditedField && (value === undefined || value === null) && field.default !== undefined) {
                const disabled = field.is_lock === true;
                if (disabled) {
                    setValue(field.id, false);
                } else {
                    setValue(field.id, field.default);
                }
            }
        });
    }, [fields, settings, isEdited, setValue, getValue]); // Re-run only when necessary

    if (!fields) return null;

    const fieldComponents: Record<string, any> = {
        two_fa_validation: TwoFaValidation,
        qr_code: QrCode,
        backup_codes: BackupCodes,
        license: License,
        checkbox: Checkbox,
        tracking_test: TrackingTest,
        email: Email,
        plugins: Plugins,
        password: Password,
        dropdown: Dropdown,
        number: NumberInputWithControls,
        multi_select: MultiSelectDropdown,
        button: ButtonInput,
        text: Text,
        textarea: Textarea,
        pricing: Pricing,
        status: Status,
    };

    //the settings contain the values.
    return (
        <ErrorBoundary>
            {fields.map((field) => {
                // 1) Evaluate visible_if (if present)
                if (field.visible_if && field.visible_if.field) {
                    const cond = field.visible_if;

                    // Try to get the current value from the store
                    let currentValue = getValue(cond.field);

                    const normalizedCurrent = normalizeValue(currentValue);
                    const normalizedEquals = normalizeValue(cond.equals);

                    if (normalizedCurrent !== normalizedEquals) {
                        // Condition not met: skip rendering this field
                        return null;
                    }
                }

                // 2) Get the value from the store. Default values are now handled in the useEffect hook.
                const value = getValue(field.id);
                const Component = fieldComponents[field.type] || null;

                // 3) Special handling for "button" field type (externalAction)
                if (field.type === 'button') {
                    const groupId = field.group_id;
                    const alertState = getAlertState(groupId);

                    const handleButtonClick = () => {
                        const externalActionName = field.externalAction;
                        const externalAction = (window as any).pluginOnboardingActions?.[externalActionName];

                        if (typeof externalAction === 'function') {
                            externalAction(
                                field,
                                settings,
                                (id: string, newState: any) => setAlertState(id, newState),
                                setValue
                            );
                        } else {
                            console.warn(`External action '${externalActionName}' not found.`);
                            setAlertState(groupId, {
                                responseMessage: `Action '${externalActionName}' is not implemented.`,
                                responseSuccess: false,
                                responseCode: 'danger',
                                isUpdating: false,
                            });
                            fieldStatus(field.id, false);
                        }
                    };

                    // Determine variant and message for Alert
                    const alertVariant = alertState.responseCode === 'loading'
                        ? 'loading'
                        : (alertState.responseCode === 'success' ? 'success' : 'danger');
                    const alertMessage = alertState.responseMessage;

                    return (
                        <div key={`${field.id}-wrapper`} className="!mt-6">
                            {field.actionType === 'connection_test' && alertState.responseMessage && (
                                <Alert
                                    variant={alertVariant}
                                    message={alertMessage}
                                    className="mb-4"
                                />
                            )}
                            <ButtonInput
                                onClick={handleButtonClick}
                                btnVariant="secondary"
                                size="md"
                                className="w-full"
                                disabled={alertState.isUpdating}
                            >
                                {field.label}
                            </ButtonInput>
                        </div>
                    );
                }

                // 4) Common props for non-button fields
                const commonProps = {
                    key: field.id,
                    field: field,
                    onChange: (val: any) => onChange(field.id, val),
                    fieldStatus: (success: boolean) => fieldStatus(field.id, success),
                    value: value,
                };

                return Component
                    ? <Component
                        {...commonProps}
                      />
                    : null;
            })}
        </ErrorBoundary>
    );
};

export default Fields;