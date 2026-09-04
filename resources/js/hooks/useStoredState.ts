import { useEffect, useRef, useState } from 'react';

export default function useStoredState<T>(key: string, defaultValue: T): [T, React.Dispatch<React.SetStateAction<T>>] {
    const [value, setValue] = useState<T>(() => {
        try {
            const stored = window.localStorage.getItem(key);
            return stored !== null ? JSON.parse(stored) : defaultValue;
        } catch {
            return defaultValue;
        }
    });

    const isFirst = useRef(true);
    useEffect(() => {
        if (isFirst.current) {
            isFirst.current = false;
            return;
        }
        try {
            window.localStorage.setItem(key, JSON.stringify(value));
        } catch {
        }
    }, [key, value]);

    return [value, setValue];
}
