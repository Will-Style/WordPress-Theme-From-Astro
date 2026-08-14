import { clsx } from "clsx";
import { type ButtonHTMLAttributes, type ReactNode, type MouseEventHandler } from "react";

interface ButtonInputProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  children: ReactNode;
  onClick?: MouseEventHandler<HTMLButtonElement>;
  link?: string;
  btnVariant?: "primary" | "secondary" | "tertiary" | "danger" | "transparent" | "transparent-disabled";
  disabled?: boolean;
  size?: "sm" | "md" | "lg";
  className?: string;
  /**
   * If provided, this will fully override all default button classes.
   * This is useful when we want schema-driven buttons to control styling completely.
   */
  rawClassName?: string;
}

/**
 * A versatile button component that can render as either a <button> or an <a> element.
 *
 * Use the `btnVariant` prop to adjust the visual style:
 * - "primary" for a green-themed button.
 * - "secondary" for a blue-themed button.
 * - "tertiary" for a neutral, gray-themed button.
 * - "danger" for a red-themed button.
 * - "transparent" for a transparent-themed button.
 * - "transparent-disabled" for a disabled-looking transparent button.
 *
 * The `size` prop controls button dimensions:
 * - "sm" for smaller padding and text.
 * - "md" for default spacing.
 * - "lg" for increased padding and larger, bolder text.
 * @param props - Props for configuring the button.
 * @param props.children - The content to display inside the button.
 * @param props.onClick - Click handler for the button.
 * @param props.link - Optional URL to render the button as an anchor tag.
 * @param props.btnVariant - Visual style variant of the button.
 * @param props.disabled - Whether the button is disabled.
 * @param props.size - Size variant of the button.
 * @param props.className - Additional CSS classes to apply.
 * @returns The rendered button or link component.
 */
const ButtonInput = ({
  children,
  onClick,
  link,
  btnVariant = "secondary",
  disabled = false,
  size = "md",
  className = "",
  rawClassName,
  ...props
}: ButtonInputProps) => {
  // If rawClassName is provided, use it as-is and skip all default styling logic.
  const classes = rawClassName
    ? rawClassName
    : clsx(
        // Base styles for all button variants
        "rounded-xl transition-all duration-200",
        // Variant-specific styles
        {
          "bg-primary border border-primary text-white hover:bg-primary hover:[box-shadow:0_0_0_3px_rgba(43,129,51,0.5)] focus:[box-shadow:0_0_0_3px_rgba(43,129,51,0.5)]":
            btnVariant === "primary",
          "bg-[var(--teamupdraft-orange-dark)] border border-[var(--teamupdraft-orange-dark)] text-white hover:[box-shadow:0_0_0_3px_#C4511C99] focus:[box-shadow:0_0_0_3px_#C4511C99]":
            btnVariant === "secondary",
          "bg-gray-100 border border-gray-400 text-gray-600 hover:bg-gray-200 hover:text-gray hover:[box-shadow:0_0_0_3px_rgba(0,0,0,0.1)] focus:[box-shadow:0_0_0_3px_rgba(0,0,0,0.1)]":
            btnVariant === "tertiary",
          "bg-red border border-red text-white hover:bg-red hover:[box-shadow:0_0_0_3px_rgba(198,39,59,0.5)] focus:[box-shadow:0_0_0_3px_rgba(198,39,59,0.5)]":
            btnVariant === "danger",
          "bg-transparent text-[var(--teamupdraft-grey-600)] hover:text-[var(--teamupdraft-grey-900)]":
            btnVariant === "transparent",
          "bg-transparent border border-[var(--teamupdraft-grey-100)] text-[var(--teamupdraft-grey-200)]":
            btnVariant === "transparent-disabled",
        },
        // Size-specific styles
        {
          "py-0.5 text-sm font-normal": size === "sm",    // Small: Reduced padding and smaller text
          "py-1.5 text-base font-medium": size === "md",       // Medium (default): Standard padding and text size
          "py-2 text-lg font-semibold": size === "lg",     // Large: Increased padding and larger, bolder text
        },
        // Disabled styles
        {
          "opacity-50 cursor-not-allowed": disabled,
        },
        className
      );

  if (link) {
    return (
      <a href={link} target={"_blank"} className={classes}>
        {children}
      </a>
    );
  }

  return (
    <button
      type={props.type || "button"}
      onClick={onClick}
      className={classes}
      disabled={disabled}
      {...props}
    >
      {children}
    </button>
  );
};

ButtonInput.displayName = "ButtonInput";

export default ButtonInput;