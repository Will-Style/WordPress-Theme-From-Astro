import * as RadixAccordion from '@radix-ui/react-accordion';
import Icon from "../../utils/Icon";
import useOnboardingStore from "../../store/useOnboardingStore";
import Fields from "./Fields";
import ButtonInput from "../Inputs/ButtonInput";
import { memo, useEffect, useState, useMemo, useCallback } from "@wordpress/element";
import { __ } from '@wordpress/i18n';

interface AccordionProps {
    fields: any[];
    groups: Array<{
        id: string;
        title: string;
        showConfirmButton?: boolean;
        hidden?: boolean;
        controllerFieldId?: string;
        /**
         * If true (or not set), when the accordion is reopened,
         * the `<groupId>_completed` status may be reset to false.
         * If false, reopening the group will not modify the `_completed` flag.
         */
        resetOnOpen?: boolean;
    }>;
    onChange: (id: string, value: any) => void;
}

const Accordion = ({
    fields,
    groups,
    onChange,
}: AccordionProps) => {
    // Retrieve state and functions from the Zustand store
    const {
        setContinueDisabled,
        continueDisabled,
        getValue,
        settings,
        setValue,
    } = useOnboardingStore();

    const [currentOpen, setCurrentOpen] = useState<string>('');
    const [failed, setFailed] = useState<Record<string, Set<string>>>({});

    /**
     * Updates the failure status of a field within a group.
     * Used by the Fields component to report validation status.
     * @param groupIdentifier The ID of the accordion group.
     * @param fieldID The ID of the validated field.
     * @param success The validation status (true if successful, false if failed).
     */
    const fieldStatus = (groupIdentifier: string, fieldID: string, success: boolean) => {
        setFailed(prevFailed => {
            const newFailedIDs = new Set(prevFailed[groupIdentifier] ?? []);

            if (success) {
                newFailedIDs.delete(fieldID);
            } else {
                newFailedIDs.add(fieldID);
            }

            // If there are no failed fields in the group, remove the group from the failed state
            if (newFailedIDs.size === 0) {
                const {[groupIdentifier]: _, ...rest} = prevFailed;
                return rest;
            } else {
                // If there are failed fields, update the failed state
                return {...prevFailed, [groupIdentifier]: newFailedIDs};
            }
        });
    }

    // Memoize the list of all groups to avoid unnecessary re-calculations
    const allGroups = useMemo(() => {
        return groups.map(group => ({ ...group, showConfirmButton: group.showConfirmButton ?? true }));
    }, [groups]);

    /**
     * Handles changes to the open/closed state of the accordion.
     * When a group is opened for the first time and it has the `resetOnOpen` flag
     * (default: true), the `<groupId>_completed` status is reset to false.
     * Validation errors for that group are always cleared when it is opened.
     * @param newOpenId The ID of the newly opened accordion group (or an empty string if all are closed).
     */
    const accordionChange = (newOpenId: string) => {
        const previousOpenId = currentOpen;

        if (newOpenId && newOpenId !== previousOpenId) {
            const openedGroup = allGroups.find(g => g.id === newOpenId);
            const shouldResetOnOpen = openedGroup?.resetOnOpen !== false; // default: true

            if (shouldResetOnOpen) {
                const completionFieldId = `${newOpenId}_completed`;
                const isCurrentlyCompleted = !!getValue(completionFieldId);
                if (isCurrentlyCompleted) {
                    // Reset completed status when the group is opened (only if allowed by flag)
                    setValue(completionFieldId, false);
                }
            }
            // ALSO: Clear any validation failures for this group when it's opened
            setFailed(prevFailed => {
                const {[newOpenId]: _, ...rest} = prevFailed;
                return rest;
            });
        }

        setCurrentOpen(newOpenId);
    }

    /**
     * Determines if an accordion group should be hidden.
     * Depends on the group's `hidden` property or the `controllerFieldId` field.
     * @param group The accordion group object.
     * @returns true if the group should be hidden, false otherwise.
     */
    const isGroupHidden = useCallback((group) => {
        let hiddenStatus = group.hidden === true;

        const groupControllerFieldId = group.controllerFieldId;
        if (groupControllerFieldId) {
            const controllerField = fields.find(f => f.id === groupControllerFieldId);

            if (controllerField && (controllerField.type === 'dropdown' || controllerField.type === 'multi_select')) {
                const rawSelectedControllerValues = getValue(groupControllerFieldId) || [];
                const selectedValuesArray = Array.isArray(rawSelectedControllerValues)
                    ? rawSelectedControllerValues
                    : (rawSelectedControllerValues !== null ? [rawSelectedControllerValues] : []);

                const isThisGroupSelectedByItsController = selectedValuesArray.some((selectedValue: any) => {
                    const option = controllerField.options?.find((opt: any) => opt.value === selectedValue);
                    return option && option.is_group === true && option.value === group.id;
                });

                hiddenStatus = selectedValuesArray.length === 0 || !isThisGroupSelectedByItsController;
            }
        }
        return hiddenStatus;
    }, [fields, getValue]);

    /**
     * Checks if all fields within a given group have valid, non-empty values.
     * This is used for groups without an explicit confirm button to determine implicit completion.
     * @param groupIdentifier The ID of the accordion group.
     * @returns true if all fields have values, false otherwise.
     */
    const allFieldsHaveValues = useCallback((groupIdentifier: string) => {
        const groupFields = fields.filter(item => item.group_id === groupIdentifier);
        if (groupFields.length === 0) {
            return true; // If no fields, it's implicitly complete
        }

        const allValid = groupFields.every(field => {
            const value = getValue(field.id);
            let isValid = false;
            if (typeof value === 'string') {
                isValid = value.trim() !== '';
            } else if (typeof value === 'boolean') {
                isValid = value === true;
            } else if (typeof value === 'number') {
                isValid = !isNaN(value);
            } else if (Array.isArray(value)) {
                isValid = value.length > 0;
            } else {
                isValid = value !== null && value !== undefined;
            }
            return isValid;
        });
        return allValid;
    }, [fields, getValue]);

    /**
     * Handles the click event for the "Confirm" button.
     * Sets the group as completed and opens the next visible group.
     * @param groupIdentifier The ID of the group being confirmed.
     */
    const handleConfirmClick = (groupIdentifier: string) => {
        if (failed[groupIdentifier] && failed[groupIdentifier].size > 0) {
            return; // Safeguard, should be disabled anyway
        }

        const completionFieldId = `${groupIdentifier}_completed`;
        setValue(completionFieldId, true);

        setFailed(prevFailed => {
            const { [groupIdentifier]: _, ...rest } = prevFailed;
            return rest;
        });

        // Find the next *visible* group to open
        const visibleGroups = allGroups.filter(g => !isGroupHidden(g));
        const currentVisibleIndex = visibleGroups.findIndex(g => g.id === groupIdentifier);

        if (currentVisibleIndex !== -1 && currentVisibleIndex < visibleGroups.length - 1) {
            const nextGroup = visibleGroups[currentVisibleIndex + 1];
            setCurrentOpen(nextGroup.id);
        } else {
            setCurrentOpen(''); // Close accordion if it's the last one
        }
    };

    /**
     * Side effect to update the 'Continue' button's status
     * based on whether all visible groups are completed.
     */
    useEffect(() => {
        const visibleGroups = allGroups.filter(group => !isGroupHidden(group));

        const allVisibleGroupsAreCompleted = visibleGroups.every(group => {
            const groupIdentifier = group.id;
            const completionFieldId = `${groupIdentifier}_completed`;
            const explicitCompletionValue = getValue(completionFieldId);
            const hasCompletionFieldInSettings = settings.some(item => item.id === completionFieldId);
            const hasValidationFailures = groupIdentifier in failed;

            let isGroupActuallyCompleted = false;
            const groupHasExplicitCompletionField = hasCompletionFieldInSettings;

            if (group.showConfirmButton) {
                // For groups with an explicit "Confirm" button, rely on its _completed status and no validation failures.
                isGroupActuallyCompleted = groupHasExplicitCompletionField && !!explicitCompletionValue && !hasValidationFailures;
            } else {
                // For groups without an explicit "Confirm" button:
                if (groupHasExplicitCompletionField) {
                    // If it has a _completed field (like remote storage), rely on that and no validation failures.
                    isGroupActuallyCompleted = !!explicitCompletionValue && !hasValidationFailures;
                } else {
                    // Otherwise (e.g., simple settings group without _completed field), rely on all fields having values and no validation failures.
                    isGroupActuallyCompleted = !hasValidationFailures && allFieldsHaveValues(groupIdentifier);
                }
            }
            return isGroupActuallyCompleted;
        });

        const calculatedIsDisabled = !allVisibleGroupsAreCompleted;

        if (calculatedIsDisabled !== continueDisabled) {
            setContinueDisabled(calculatedIsDisabled);
        }
    }, [
        allGroups,
        setContinueDisabled,
        continueDisabled,
        isGroupHidden,
        getValue, 
        settings, 
        failed, 
        allFieldsHaveValues 
    ]);

    return (
        <RadixAccordion.Root
            type="single"
            value={currentOpen}
            onValueChange={accordionChange}
            collapsible
        >
            {allGroups.map((group) => {
                let isGroupHiddenLocal = group.hidden === true;

                const groupControllerFieldId = group.controllerFieldId;
                if (groupControllerFieldId) {
                    const controllerField = fields.find(f => f.id === groupControllerFieldId);
                    const isManagedByController = controllerField?.options?.some((opt: any) => opt.value === group.id && opt.is_group === true);
                    if (isManagedByController) {
                        const rawSelectedControllerValues = getValue(groupControllerFieldId) || [];
                        const selectedValuesArray = Array.isArray(rawSelectedControllerValues)
                            ? rawSelectedControllerValues
                            : (rawSelectedControllerValues !== null ? [rawSelectedControllerValues] : []);
                        
                        if (selectedValuesArray.length === 0) {
                            isGroupHiddenLocal = true;
                        } else {
                            const selectedGroupIds = selectedValuesArray.filter((value: any) => {
                                const option = controllerField?.options?.find((opt: any) => opt.value === value);
                                return option && option.is_group === true;
                            });
                            isGroupHiddenLocal = !selectedGroupIds.includes(group.id);
                        }
                    }
                }

                if (isGroupHiddenLocal) {
                    return null;
                }

                const groupIdentifier = group.id;
                const completionFieldId = `${groupIdentifier}_completed`;
                const explicitCompletionValue = getValue(completionFieldId);
                const hasCompletionFieldInSettings = settings.some(item => item.id === completionFieldId);
                const hasValidationFailures = groupIdentifier in failed;

                let isGroupActuallyCompleted = false;
                const groupHasExplicitCompletionField = hasCompletionFieldInSettings;

                if (group.showConfirmButton) {
                    isGroupActuallyCompleted = groupHasExplicitCompletionField && !!explicitCompletionValue && !hasValidationFailures;
                } else {
                    if (groupHasExplicitCompletionField) {
                        isGroupActuallyCompleted = !!explicitCompletionValue && !hasValidationFailures;
                    } else {
                        isGroupActuallyCompleted = !hasValidationFailures && allFieldsHaveValues(groupIdentifier);
                    }
                }

                return (
                    <RadixAccordion.Item key={groupIdentifier} className="rounded-xl border border-grey data-[state=open]:border-orange-darkish overflow-hidden my-2" value={groupIdentifier}>
                        <RadixAccordion.Header className="flex">
                            <RadixAccordion.Trigger className="group font-semibold text-md px-3.5 h-[45px] flex-1 flex items-center justify-between bg-white hover:data-[state=closed]:bg-blue-lightest">
                                {group.title}
                                {isGroupActuallyCompleted
                                        ? <Icon
                                            name="success"
                                            size={24}
                                            strokeWidth={1}
                                            stroke="none"
                                            color="#15803D"
                                            fill="#15803D"
                                        />
                                        : <Icon
                                            name="expand"
                                            size={24}
                                            strokeWidth={1}
                                            stroke="none"
                                            color="gray-500"
                                            fill="gray-500"
                                        />
                                }
                            </RadixAccordion.Trigger>
                        </RadixAccordion.Header>
                        <RadixAccordion.Content className="data-[state=open]:animate-slideDown data-[state=closed]:animate-slideUp">
                            <div className="mx-3.5 my-3.5 space-y-1">
                                <Fields
                                    fields={fields.filter(item => item.group_id === group.id)}
                                    onChange={onChange}
                                    fieldStatus={(fieldID, success) => fieldStatus(groupIdentifier, fieldID, success)}
                                />
                            </div>
                            {group.showConfirmButton && (
                                <div className="flex flex-row gap-4 justify-center items-center min-w-[32ch]">
                                    <ButtonInput
                                        className="w-full burst-continue flex justify-center items-center outline-none px-2 m-3.5"
                                        btnVariant="secondary"
                                        size="md"
                                        onClick={() => handleConfirmClick(groupIdentifier)}
                                        disabled={groupIdentifier in failed}
                                    >
                                        {__('Confirm', 'ONBOARDING_WIZARD_TEXT_DOMAIN')}
                                    </ButtonInput>
                                </div>
                            )}
                        </RadixAccordion.Content>
                    </RadixAccordion.Item>
                );
            })}
        </RadixAccordion.Root>
    )
};

export default memo(Accordion);