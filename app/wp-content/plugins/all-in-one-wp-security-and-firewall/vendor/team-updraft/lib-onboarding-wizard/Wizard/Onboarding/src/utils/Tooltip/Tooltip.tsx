import { type ReactNode } from 'react';
import * as TooltipUI from '@radix-ui/react-tooltip';
import Icon from '../Icon';
import { useTooltipContainer } from './TooltipContainerContext';
import {useState} from "@wordpress/element";
import {renderPossiblyHtml, isLikelyHtml} from '../html';
import { clsx } from 'clsx';
export interface TooltipProps {
    heading?: string | {
        text: string;
        icon?: string;
    };
    text?: ReactNode | string;
    side?: "bottom" | "left" | "right" | "top";
    align?: "center" | "start" | "end";
}

interface Props {
    children: ReactNode;
    tooltip: TooltipProps;
    delayDuration?: number;
    triggerClassName?: string;
}

const Tooltip = ({
    children,
    tooltip,
    delayDuration = 400,
    triggerClassName,
}: Props) => {
    const container = useTooltipContainer() ?? undefined;
    const [open, setOpen] = useState(false);

    const side = tooltip.side ?? 'right';
    const align = tooltip.align ?? 'center';

    // Function to render tooltip content based on type
    const renderContent = () => {
        const heading = tooltip.heading;
        const text = tooltip.text;

        return (
            <div className="flex flex-col gap-1">
                {heading && (
                    typeof heading === 'string' ? (
                        <h4 className="text-lg font-medium">{renderPossiblyHtml(heading)}</h4>
                    ) : (
                        <div className="flex flex-row items-center gap-1">
                            <h4 className="text-lg font-medium">{heading.text}</h4>
                            {heading.icon && (
                                <Icon
                                    name={heading.icon}
                                    color="var(--teamupdraft-orange-dark)"
                                    size={14}
                                    className="ml-1"
                                />
                            )}
                        </div>
                    )
                )}
                {typeof text === 'string' ? (
                    // Conditional rendering based on whether text is likely HTML
                    isLikelyHtml(text) ? (
                        renderPossiblyHtml(text) // Render directly if it's HTML (will be a div)
                    ) : (
                        <p className="text-md">{renderPossiblyHtml(text)}</p> // Wrap in <p> if it's plain text
                    )
                ) : (
                    text && <p className="text-md">{text}</p>
                )}
            </div>
        );
    };

    return (
        <TooltipUI.Provider delayDuration={delayDuration}>
            <TooltipUI.Root open={open} onOpenChange={setOpen}>
                <TooltipUI.Trigger asChild>
                    <div
                        onClick={(e) => {
                            e.preventDefault();
                            setOpen((prev) => !prev); // toggle on click
                        }}
                        className={clsx("w-fit", triggerClassName)}
                    >
                        {children}
                    </div>
                </TooltipUI.Trigger>
                <TooltipUI.Portal container={container}>
                    <TooltipUI.Content
                        className="burst-tooltip-content bg-[var(--teamupdraft-grey-900)] text-white max-w-[195px] rounded-md px-3 py-2 gap-1 z-10"
                        side={side}
                        align={align}
                        sideOffset={5}
                    >
                        {renderContent()}
                        <TooltipUI.Arrow style={{ fill: "var(--teamupdraft-grey-900)" }} className="burst-tooltip-arrow" />
                    </TooltipUI.Content>
                </TooltipUI.Portal>
            </TooltipUI.Root>
        </TooltipUI.Provider>
    );
};

export default Tooltip;