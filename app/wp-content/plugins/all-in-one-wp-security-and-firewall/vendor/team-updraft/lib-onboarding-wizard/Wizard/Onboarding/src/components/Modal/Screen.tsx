import { forwardRef, memo } from "@wordpress/element";
import { sprintf, __ } from "@wordpress/i18n";
// @ts-ignore
import useOnboardingStore from "@/store/useOnboardingStore";
import { clsx } from "clsx";

export const Top = memo(forwardRef<HTMLDivElement, { className?: string }>(
    ({ className }, ref) => {
        const { onboardingData } = useOnboardingStore();

        return (
            <div ref={ref} className={clsx('default-style', className)}>
                <div className="flex flex-row items-center space-x-2 px-4">
                    {onboardingData.logo && (
                        <img src={onboardingData.logo} alt="Logo" className="h-8 w-auto" />
                    )}
                    <div className="font-semibold text-2xl sm:text-4xl leading-snug sm:leading-7 text-center">
                        {sprintf(__('Welcome to %s!', 'ONBOARDING_WIZARD_TEXT_DOMAIN'), onboardingData.plugin_name)}
                    </div>
                </div>
            </div>
        );
    }
));

export const Bottom = memo(forwardRef<HTMLDivElement, { className?: string; handleClose: () => void }>(
    ({ className, handleClose }, ref) => {
        const { onboardingData, isLastStep } = useOnboardingStore();
        return (
            <div ref={ref} className={clsx('default-style', className, isLastStep() ? 'opacity-0 pointer-events-none' : 'opacity-100')}>
                <button
                    onClick={handleClose}
                        className="text-gray-500 text-md underline"
                >
                    {__(onboardingData.exit_wizard_text ?? 'Exit setup', 'ONBOARDING_WIZARD_TEXT_DOMAIN')}
                </button>
            </div>
        );
    }
));