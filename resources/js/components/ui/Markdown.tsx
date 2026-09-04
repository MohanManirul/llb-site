import ReactMarkdown, { type Components } from 'react-markdown';
import remarkGfm from 'remark-gfm';

/*
 * Markdown — renders authored text (product copy, notes) as formatted content.
 *
 * Raw HTML is deliberately not enabled: this text comes from a form, and
 * react-markdown escapes embedded tags unless rehype-raw is added. Do not add
 * it without a sanitiser.
 *
 * Every element is styled here because the project has no typography plugin;
 * the defaults would otherwise render headings and lists as plain paragraphs.
 */
export interface MarkdownProps {
    children?: string | null;
    className?: string;
}

const components: Components = {
    h1: ({ children }) => (
        <h1 className="mt-6 mb-3 border-b border-gray-200 pb-2 text-lg font-semibold text-gray-900 first:mt-0">
            {children}
        </h1>
    ),
    h2: ({ children }) => (
        <h2 className="mt-6 mb-2 text-base font-semibold text-gray-900 first:mt-0">
            {children}
        </h2>
    ),
    h3: ({ children }) => (
        <h3 className="mt-5 mb-2 text-sm font-semibold text-gray-900 first:mt-0">
            {children}
        </h3>
    ),
    h4: ({ children }) => (
        <h4 className="mt-4 mb-1 text-sm font-semibold text-gray-700 first:mt-0">
            {children}
        </h4>
    ),
    p: ({ children }) => (
        <p className="my-3 text-sm leading-relaxed text-gray-700 first:mt-0 last:mb-0">
            {children}
        </p>
    ),
    strong: ({ children }) => (
        <strong className="font-semibold text-gray-900">{children}</strong>
    ),
    em: ({ children }) => <em className="italic">{children}</em>,
    ul: ({ children }) => (
        <ul className="my-3 list-disc space-y-1.5 pl-5 text-sm leading-relaxed text-gray-700 marker:text-gray-400">
            {children}
        </ul>
    ),
    ol: ({ children }) => (
        <ol className="my-3 list-decimal space-y-1.5 pl-5 text-sm leading-relaxed text-gray-700 marker:text-gray-400">
            {children}
        </ol>
    ),
    li: ({ children }) => <li className="pl-1">{children}</li>,
    a: ({ href, children }) => (
        <a
            href={href}
            target="_blank"
            rel="noopener noreferrer"
            className="font-medium text-blue-600 underline underline-offset-2 hover:text-blue-800"
        >
            {children}
        </a>
    ),
    blockquote: ({ children }) => (
        <blockquote className="my-3 border-l-2 border-gray-300 pl-3 text-sm italic text-gray-600">
            {children}
        </blockquote>
    ),
    code: ({ children }) => (
        <code className="rounded bg-gray-100 px-1 py-0.5 font-mono text-[12px] text-gray-800">
            {children}
        </code>
    ),
    pre: ({ children }) => (
        <pre className="my-3 overflow-x-auto rounded-lg bg-gray-900 p-3 font-mono text-[12px] text-gray-100">
            {children}
        </pre>
    ),
    hr: () => <hr className="my-5 border-gray-200" />,
    table: ({ children }) => (
        <div className="my-3 overflow-x-auto">
            <table className="min-w-full border border-gray-200 text-sm">
                {children}
            </table>
        </div>
    ),
    thead: ({ children }) => <thead className="bg-gray-50">{children}</thead>,
    th: ({ children }) => (
        <th className="border border-gray-200 px-3 py-2 text-left font-semibold text-gray-900">
            {children}
        </th>
    ),
    td: ({ children }) => (
        <td className="border border-gray-200 px-3 py-2 text-gray-700">
            {children}
        </td>
    ),
};

export default function Markdown({ children, className = '' }: MarkdownProps) {
    if (!children) {
        return null;
    }

    return (
        <div className={className}>
            <ReactMarkdown remarkPlugins={[remarkGfm]} components={components}>
                {children}
            </ReactMarkdown>
        </div>
    );
}
