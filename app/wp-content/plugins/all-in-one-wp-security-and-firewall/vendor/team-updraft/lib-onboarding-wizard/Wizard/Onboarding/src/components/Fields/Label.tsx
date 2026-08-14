import * as RadixLabel from "@radix-ui/react-label";
import { memo, type ReactNode } from "react";
import { clsx } from "clsx";
import Icon from "../../utils/Icon";
import Tooltip from "../../utils/Tooltip/Tooltip";
import type { TooltipProps } from "../../utils/Tooltip/Tooltip";

interface LabelProps {
    htmlFor?: string;
    text: ReactNode;
    tooltip?: TooltipProps;
    className?: string;
}

const Label = ({
    htmlFor,
    text,
    tooltip,
    className,
}: LabelProps) => {
    return (
        <RadixLabel.Root
            htmlFor={htmlFor}
            className={clsx("flex items-center gap-2 text-md font-medium text-black cursor-pointer", className)}
        >
            <span>{text}</span>
            {tooltip && (
                <Tooltip tooltip={tooltip} triggerClassName="ml-[-4px]">
                    <Icon
                        name={'info'}
                        color="gray500"
                        fill="gray500"
                        size={16}
                    />
                </Tooltip>
            )}
        </RadixLabel.Root>
    );
};

export default memo(Label);