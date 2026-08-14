import * as Dialog from '@radix-ui/react-dialog';
import * as VisuallyHidden from "@radix-ui/react-visually-hidden";
import * as Screen from './Screen';

const Modal = ({
    logo,
    logo_class,
    title,
    currentStepIndex,
    content,
    footer,
    triggerClassName,
    children,
    isOpen,
    onClose
}) => {

    return (
        <Dialog.Root
            open={isOpen}
            onOpenChange={( open ) => {
                if ( ! open ) {
                    onClose?.();
                }
            }}
        >
            {triggerClassName && (
                <Dialog.Trigger className={triggerClassName}>{children}</Dialog.Trigger>
            )}
            <Dialog.Portal container={document.getElementById( 'onboarding-modal-root' )}>
                <Dialog.Overlay className="bg-blue-lightest fixed inset-0 z-[100001]" />
                <Dialog.Content
                    onOpenAutoFocus={(e) => e.preventDefault()}
                    onInteractOutside={(event) => event.preventDefault()}
                    onEscapeKeyDown={(event) => event.preventDefault()}
                    className="fixed inset-0 z-[100002] py-[24px]"
                >
                    <div className="flex h-full w-full flex-col justify-between overflow-y-auto custom-scrollbar">
                        <Screen.Top className="shrink-0 mx-auto" />
                        <div className="w-full my-6 py-2 md:my-8 md:p-4">
                            <div className="mx-auto w-[94vw] sm:w-[70vw] max-w-[480px] p-6 rounded-2xl bg-white focus:outline-none data-[state=open]:animate-contentShow">
                                {(currentStepIndex === 0) && (
                                    <div className="mt-2">
                                        {logo && (
                                            <img src={logo} alt="Logo" className={`h-[54px] w-auto mx-auto ${logo_class || ''}`} />
                                        )}
                                        <Dialog.Title className="text-lg font-semibold text-black">
                                            <VisuallyHidden.Root>Onboarding</VisuallyHidden.Root>
                                        </Dialog.Title>
                                    </div>
                                )}
                                <Dialog.Description className="sr-only">
                                    {title} - {typeof content === 'string' ? content : 'Onboarding step content'}
                                </Dialog.Description>
                                <div className="text-base text-black mb-3">{content}</div>
                                {footer && (
                                    <div className="flex flex-row justify-end gap-2">{footer}</div>
                                )}
                            </div>
                        </div>
                        <Screen.Bottom className="shrink-0 mx-auto w-full text-center" handleClose={onClose} />
                    </div>
                </Dialog.Content>
            </Dialog.Portal>
        </Dialog.Root>
    );
};

export default Modal;
