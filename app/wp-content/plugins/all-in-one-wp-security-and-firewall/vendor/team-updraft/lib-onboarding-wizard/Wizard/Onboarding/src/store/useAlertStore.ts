import { create } from 'zustand';

export interface AlertState {
    isUpdating: boolean;
    responseMessage: string;
    responseSuccess: boolean;
    responseCode: boolean | string;
}

interface AlertStore {
    alertStates: Record<string, AlertState>;
    setAlertState: (id: string, newState: Partial<AlertState>) => void;
    getAlertState: (id: string) => AlertState;
}

const defaultAlertState: AlertState = {
    isUpdating: false,
    responseMessage: '',
    responseSuccess: false,
    responseCode: false,
};

const useAlertStore = create<AlertStore>((set, get) => ({
    alertStates: {},
    setAlertState: (id, newState) => {
        set((state) => ({
            alertStates: {
                ...state.alertStates,
                [id]: {
                    ...get().getAlertState(id), // Get current state to merge
                    ...newState,
                },
            },
        }));
    },
    getAlertState: (id) => {
        return get().alertStates[id] || defaultAlertState;
    },
}));

export default useAlertStore;