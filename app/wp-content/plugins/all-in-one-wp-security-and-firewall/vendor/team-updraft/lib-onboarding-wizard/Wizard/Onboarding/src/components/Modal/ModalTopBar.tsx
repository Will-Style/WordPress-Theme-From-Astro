import ButtonInput from "../Inputs/ButtonInput";
// @ts-ignore
import Icon from '@/utils/Icon';
import { sprintf, __ } from "@wordpress/i18n";
import ProgressBar from "../ProgressBar";
import useOnboardingStore from "../../store/useOnboardingStore";

export const ModalTopBar = ({ handlePrevious, currentStep }) => {
    const {
        currentStepIndex,
        steps,
        isLastStep,
    } = useOnboardingStore();

    return (
        <>
            {(currentStepIndex > 0) && (
                <div className="w-full mb-4">
                    <div className="flex items-center gap-2 justify-between">
                        <ButtonInput
                            className="flex items-center gap-2 justify-center text-lg pl-1"
                            btnVariant="transparent"
                            size="sm"
                            onClick={(e) => handlePrevious()}
                            key={currentStep.id + "previous"}
                        >
                            <Icon name="WestRoundedIcon" type="material" size={15} color="gray500" fill="gray500" />

                            {__('Back', 'ONBOARDING_WIZARD_TEXT_DOMAIN')}
                        </ButtonInput>
                        <div className="text-sm text-gray-600">
                            {sprintf(__('Step %1$d of %2$d', 'ONBOARDING_WIZARD_TEXT_DOMAIN'), currentStepIndex, steps.length - 1)}
                        </div>
                    </div>

                    <ProgressBar
                        currentStep={currentStepIndex - 1}
                        totalSteps={steps.length - 1}
                    />
                </div>
            )}
        </>
    );
}