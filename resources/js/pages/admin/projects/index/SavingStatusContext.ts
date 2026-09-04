import { createContext, useContext } from 'react';

export const SavingStatusContext = createContext<number | null>(null);

export function useSavingStatusId(): number | null {
    return useContext(SavingStatusContext);
}
