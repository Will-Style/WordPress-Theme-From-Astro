// @ts-ignore
import Icon from '@/utils/Icon';
import useOnboardingStore from "../../store/useOnboardingStore";
import {renderPossiblyHtml} from '../../utils/html';

export const ModalHeader = ({ currentStep }) => {
    const {
        isInstalling,
        isUpdating,
        responseMessage,
        responseSuccess,
        licenseStatus,
        getConditionalText,
    } = useOnboardingStore();

    const title = getConditionalText(currentStep, 'title', { isUpdating, isInstalling, responseMessage, responseSuccess, licenseStatus }, currentStep.title);
    const subtitle = getConditionalText(currentStep, 'subtitle', { isUpdating, isInstalling, responseMessage, responseSuccess, licenseStatus }, currentStep.subtitle);

    const iconColor = currentStep.type === "license" && licenseStatus === 'activated' ? 'green' : 'var(--teamupdraft-orange-dark)';
    const iconName = currentStep.type === "license" && licenseStatus === 'activating' ? 'loading-circle' : currentStep.icon;

    return (
        <div className="flex flex-col gap-2 justify-center items-center">
            {/* Step Icon - Only render if an icon is provided */}
            {currentStep.icon && (
                <Icon
                    name={iconName}
                    size={60}
                    type=""
                    strokeWidth={1}
                    stroke="none"
                    color={iconColor}
                    fill={iconColor}
                    className="bg-[#fff5eb] rounded-[80px] p-2 shadow-[0_0_10px_#fff5eb] border border-[#fff5eb]"
                />
            )}

            <div className="text-3xl font-semibold text-[var(--teamupdraft-grey-900)] text-center mt-2">
                {title}
            </div>
            <p className="text-[var(--teamupdraft-grey-700)] text-lg leading-relaxed font-normal text-center">
                {renderPossiblyHtml(subtitle)}
            </p>
        </div>
    );
}