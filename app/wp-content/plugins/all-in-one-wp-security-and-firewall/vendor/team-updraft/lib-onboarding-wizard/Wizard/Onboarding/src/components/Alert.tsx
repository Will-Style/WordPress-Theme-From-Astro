"use client";

import { memo } from 'react';
import Icon from '../utils/Icon';
import { __ } from '@wordpress/i18n';
import { clsx } from 'clsx';
import type { IconName } from '../utils/Icon';
import { renderPossiblyHtml } from '../utils/html';

export interface AlertProps {
    variant?: 'primary' | 'secondary' | 'success' | 'danger' | 'warning' | 'info' | 'loading';
    title?: string;
    message: string;
    icon?: IconName;
    className?: string;
}

const Alert = ({
    variant = 'info',
    title,
    message,
    icon,
    className,
}: AlertProps) => {
    let resolvedIconName: IconName = 'info';
    let resolvedTitle: string = '';
    let displayBorderClass = '';
    let displayBgClass = '';
    let displayTextColor = 'text-[#4F565B]'; // Default text color for message
    let displayIconColor = 'var(--teamupdraft-grey-700)';
    let displayIconFill = 'var(--teamupdraft-grey-700)';

    switch (variant) {
        case 'primary':
            resolvedIconName = 'info';
            resolvedTitle = __("Information", "ONBOARDING_WIZARD_TEXT_DOMAIN");
            displayIconColor = 'var(--teamupdraft-blue)';
            displayIconFill = 'var(--teamupdraft-blue)';
            displayBorderClass = 'border-blue';
            displayBgClass = 'bg-blue-lightest';
            displayTextColor = 'text-blue-dark';
            break;
        case 'success':
            resolvedIconName = 'success';
            resolvedTitle = __("Success", "ONBOARDING_WIZARD_TEXT_DOMAIN");
            displayIconColor = 'var(--teamupdraft-green)';
            displayIconFill = 'var(--teamupdraft-green)';
            displayBorderClass = 'border-green';
            displayBgClass = 'bg-green-light';
            displayTextColor = 'text-green';
            break;
        case 'danger':
            resolvedIconName = 'error';
            resolvedTitle = __("Error", "ONBOARDING_WIZARD_TEXT_DOMAIN");
            displayIconColor = '#B40000';
            displayIconFill = '#B40000';
            displayBorderClass = 'border-[#FECACA]';
            displayBgClass = 'bg-[#FEF2F2]';
            displayTextColor = 'text-[#B40000]';
            break;
        case 'warning':
            resolvedIconName = 'warning';
            resolvedTitle = __("Warning", "ONBOARDING_WIZARD_TEXT_DOMAIN");
            displayIconColor = 'var(--teamupdraft-orange-dark)';
            displayIconFill = 'var(--teamupdraft-orange-dark)';
            displayBorderClass = 'border-orange';
            displayBgClass = 'bg-orange-light';
            displayTextColor = 'text-orange-darkish';
            break;
        case 'info':
            resolvedIconName = 'info';
            resolvedTitle = __("Information", "ONBOARDING_WIZARD_TEXT_DOMAIN");
            displayIconColor = 'var(--teamupdraft-blue)';
            displayIconFill = 'var(--teamupdraft-blue)';
            displayBorderClass = 'border-blue';
            displayBgClass = 'bg-blue-lightest';
            displayTextColor = 'text-blue-dark';
            break;
        case 'loading':
            resolvedIconName = 'loading-circle';
            resolvedTitle = __("Loading...", "ONBOARDING_WIZARD_TEXT_DOMAIN");
            displayIconColor = 'var(--teamupdraft-orange-dark)';
            displayIconFill = 'var(--teamupdraft-orange-dark)';
            displayBorderClass = 'border-gray-300';
            displayBgClass = 'bg-gray-100';
            displayTextColor = 'text-[#4F565B]';
            break;
        default: // 'secondary' or any other unknown variant
            resolvedIconName = 'info';
            resolvedTitle = __("Information", "ONBOARDING_WIZARD_TEXT_DOMAIN");
            displayIconColor = 'var(--teamupdraft-grey-700)';
            displayIconFill = 'var(--teamupdraft-grey-700)';
            displayBorderClass = 'border-gray-300';
            displayBgClass = 'bg-gray-100';
            displayTextColor = 'text-[#4F565B]';
            break;
    }

    // Override with props if explicitly provided
    const finalIconName = icon !== undefined ? icon : resolvedIconName;
    const finalTitle = title !== undefined ? title : resolvedTitle;

    return (
        <div className={clsx(
            "flex items-start gap-2 rounded-md border px-4 py-3 text-sm mt-2",
            displayBorderClass,
            displayBgClass,
            className
        )}>
            <Icon
                name={finalIconName}
                color={displayIconColor}
                fill={displayIconFill}
                size={16}
                className="mr-2 mt-[3px]"
            />
            <div>
                {finalTitle && (
                    <p className="font-semibold text-[#1C252C]">
                        {finalTitle}
                    </p>
                )}
                <div className={displayTextColor}>
                    {renderPossiblyHtml(message)}
                </div>
            </div>
        </div>
    );
};

export default memo(Alert);