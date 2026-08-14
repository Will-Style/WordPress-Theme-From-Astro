import { memo } from '@wordpress/element';
import FieldWrapper from './FieldWrapper';
import Icon from '../../utils/Icon';

interface PricingPlan {
    storage: string;
    description: string;
    price: string;
    period: string;
    button: string;
    card_class?: string;
    button_class?: string;
    icon?: string;
    icon_size?: number;
    icon_color?: string;
    url?: string;
}

interface PricingFieldConfig {
    id: string;
    label?: string;
    context?: any;
    context_html?: string;
    title?: string;
    title_class?: string;
    grid_class?: string;
    url?: string;
    plans?: PricingPlan[];
    default_card_class?: string;
    default_button_class?: string;
    default_icon?: string;
    default_icon_size?: number;
    default_icon_color?: string;
    [key: string]: any;
}

interface PricingProps {
    field: PricingFieldConfig;
    value?: any;
    onChange?: (value: any) => void;
}

const Pricing = ({ field }: PricingProps) => {
    const {
        title = '',
        title_class = 'text-[17px] mb-[14px] text-gray-900 mt-0',
        grid_class = 'grid grid-cols-2 gap-[10px]',
        url = '',
        plans = [],
        default_card_class = 'bg-gray-100 border-gray-300',
        default_button_class = 'rounded-xl bg-white text-gray-900 border border-gray-300 hover:bg-gray-100',
        default_icon = '',
        default_icon_size = 8,
        default_icon_color = 'currentColor',
    } = field;

    const renderPlanCard = (plan: PricingPlan, index: number) => {
        const cardClass = plan.card_class || default_card_class;
        const buttonClass = plan.button_class || default_button_class;
        const icon = plan.icon || default_icon;
        const iconSize = plan.icon_size !== undefined ? plan.icon_size : default_icon_size;
        const iconColor = plan.icon_color || default_icon_color;
        const planUrl = plan.url || url;

        return (
            <div
                key={index}
                className={`p-[14px] rounded-[12px] shadow-sm border flex flex-col justify-between ${cardClass}`}
            >
                <div>
                    <h2 className="text-[19px] font-bold mb-[5px] mt-0 !text-[#C4511C]">
                        {plan.storage}
                    </h2>
                    <p className="text-[15px] text-gray-600 mb-[10px] leading-[1.4]">
                        {plan.description}
                    </p>
                </div>

                <div className="mt-auto">
                    <div className="mb-[10px]">
                        <span className="text-[24px] font-bold text-gray-900">
                            {plan.price}
                        </span>
                        <span className="text-[14px] text-gray-500">
                            {plan.period}
                        </span>
                    </div>

                    <a
                        href={planUrl}
                        target="_blank"
                        rel="noopener noreferrer"
                        className={`w-full py-[8px] px-[12px] text-[11px] font-semibold rounded-[7px] cursor-pointer flex items-center justify-center gap-[5px] transition-colors ${buttonClass}`}
                    >
                        {plan.button}
                        {icon && (
                            <Icon
                                name={icon}
                                size={iconSize}
                                fill={iconColor}
                                className="inline-block"
                            />
                        )}
                    </a>
                </div>
            </div>
        );
    };

    return (
        <FieldWrapper
            inputId={field.id}
            label={field.label || ''}
            context={field.context}
            contextHtml={field.context_html ?? ''}
        >
            <div className="w-full">
                <hr className="mb-[10px] mt-[10px]"/>

                {title && (
                    <h1 className={title_class}>
                        {title}
                    </h1>
                )}
                
                <div className={grid_class}>
                    {plans.map((plan, index) => renderPlanCard(plan, index))}
                </div>
            </div>
        </FieldWrapper>
    );
};

export default memo(Pricing);
