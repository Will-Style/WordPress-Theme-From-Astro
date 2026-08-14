const ProgressBar = ({ currentStep, totalSteps }) => {
    const progressPercentage = ((currentStep + 1) / totalSteps) * 100;

    return (
        <div className="w-full">
            <div className="w-full bg-gray-200 rounded-full h-1">
                <div className="bg-[var(--teamupdraft-orange-dark)] h-1 rounded-full transition-all duration-300 ease-in-out"
                    style={{ width: `${progressPercentage}%` }}
                ></div>
            </div>
        </div>
    );
};

export default ProgressBar; 