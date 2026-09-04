import {
    createContext,
    useContext,
    useState,
    type Dispatch,
    type ReactNode,
    type SetStateAction,
} from 'react';

interface PageTitleValue {
    title: string;
    setTitle: Dispatch<SetStateAction<string>>;
}

const PageTitleContext = createContext<PageTitleValue>({
    title: '',
    setTitle: () => {},
});

export function PageTitleProvider({ children }: { children: ReactNode }) {
    const [title, setTitle] = useState('');

    return (
        <PageTitleContext.Provider value={{ title, setTitle }}>
            {children}
        </PageTitleContext.Provider>
    );
}

export function usePageTitle(): PageTitleValue {
    return useContext(PageTitleContext);
}
