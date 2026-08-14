"use client";

import * as React from "react";
import { memo } from "react";
import {__} from "@wordpress/i18n";
import {clsx} from "clsx";
import type { TooltipProps } from "../../utils/Tooltip/Tooltip";
import { renderPossiblyHtml } from '../../utils/html';
import Label from './Label';

interface FieldWrapperProps {
    label: React.ReactNode;
    // Allow context to be a ReactNode or an object with text and url.
    context?: React.ReactNode | { text?: string; url?: string };
    help?: string;
    error?: string;
    reverseLabel?: boolean;
    alignWithLabel?: boolean;
    className?: string;
    inputId: string;
    required?: boolean;
    children: React.ReactNode;
    pro?: { url?: string };
    tooltip?: TooltipProps;
    contextHtml?: string;
}

const isContextObject = (value: any): value is { text?: string; url?: string } => {
    return value && typeof value === "object" && !React.isValidElement(value);
};

const FieldWrapper = memo(({
    label,
    context,
    help,
    error,
    reverseLabel = false,
    alignWithLabel = false,
    className = "",
    inputId,
    required = false,
    children,
    pro,
    tooltip,
    contextHtml,
}: FieldWrapperProps) => {


    // Outer wrapper with conditional error background
    const wrapperClasses = clsx(className, "w-full box-border ", error && "bg-red-100");

    // Use a flex container that is row when aligning side-by-side and column otherwise.
    const containerClasses = alignWithLabel ? "flex flex-row items-center justify-between" : "flex flex-col";

    // Compute order classes based on reverseLabel.
    const labelOrderClass = reverseLabel ? "order-2" : "order-1";
    const fieldOrderClass = reverseLabel ? "order-1" : "order-2";

    // Margins based on horizontal or vertical layout.
    const labelMargin = alignWithLabel ? (reverseLabel ? "ml-4" : "") : (reverseLabel ? "mt-1" : "");
    const fieldMargin = alignWithLabel ? (!reverseLabel ? "ml-4" : "") : (!reverseLabel ? "mt-0" : "");

    const labelBlock = (
        <div className={clsx("flex items-center gap-2", labelMargin)}>
            <Label
                htmlFor={inputId}
                text={label}
                {...(tooltip && { tooltip })}
                className="cursor-pointer text-md font-medium text-black"
            />
            {required && (
                <span className="text-gray ml-1 text-xs font-normal">
                    ({__("Required", "ONBOARDING_WIZARD_TEXT_DOMAIN")})
                </span>
            )}
            {help && (
                <span className="text-gray ml-2 text-sm font-light">
                    {help}
                </span>
            )}
        </div>
    );

    const fieldBlock = (
        <div className={clsx("w-full", fieldMargin)}>
            {children}
        </div>
    );

    return (
        <div className={wrapperClasses}>
            <div className={containerClasses}>
                <div className={clsx(labelOrderClass)}>
                    {labelBlock}
                </div>
                <div className={clsx(fieldOrderClass)}>
                    {fieldBlock}
                </div>
            </div>

            {error && (
                <p className="text-red mt-2 text-sm font-semibold" role="alert">
                    {error}
                </p>
            )}

            {context && (
                <p className="text-gray mt-2 text-sm font-normal">
                    {isContextObject(context) ? context.text : context}
                    {isContextObject(context) && context.url && ' '}
                    {isContextObject(context) && context.url && (
                        <a rel="noopener noreferrer nofollow" className="text-blue underline" href={context.url} target="_blank">
                            {__("More info", "ONBOARDING_WIZARD_TEXT_DOMAIN")}
                        </a>
                    )}
                </p>
            )}

            {contextHtml && (
                <div className="w-full text-right mt-2 text-sm font-normal text-gray">
                    {renderPossiblyHtml(contextHtml)}
                </div>
            )}
        </div>
    );
});

FieldWrapper.displayName = "FieldWrapper";

export default FieldWrapper;