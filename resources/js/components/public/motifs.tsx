interface ArtProps {
    className?: string;
}

export function CourtSceneArt({ className }: ArtProps) {
    return (
        <svg
            viewBox="0 0 560 340"
            fill="none"
            aria-hidden="true"
            className={className}
        >
            <line x1="12" y1="300" x2="548" y2="300" stroke="var(--color-brass)" strokeWidth="2" strokeLinecap="round" opacity="0.8" />
            <line x1="30" y1="310" x2="120" y2="310" stroke="var(--color-brass)" strokeWidth="1.5" strokeLinecap="round" opacity="0.35" />
            <line x1="410" y1="310" x2="530" y2="310" stroke="var(--color-brass)" strokeWidth="1.5" strokeLinecap="round" opacity="0.35" />

            <g>
                <ellipse cx="95" cy="125" rx="72" ry="48" fill="var(--color-banyan)" opacity="0.45" />
                <ellipse cx="175" cy="98" rx="82" ry="52" fill="var(--color-banyan)" opacity="0.55" />
                <ellipse cx="235" cy="140" rx="56" ry="36" fill="var(--color-banyan)" opacity="0.45" />
                <ellipse cx="150" cy="140" rx="90" ry="42" fill="var(--color-banyan)" opacity="0.35" />

                <path
                    d="M138 300 C140 260 136 220 142 190 C146 172 150 165 152 158 C154 165 158 172 161 190 C166 220 163 260 165 300"
                    stroke="var(--color-brass)"
                    strokeWidth="3"
                    strokeLinecap="round"
                />
                <path d="M150 175 C138 160 122 152 105 148" stroke="var(--color-brass)" strokeWidth="2.5" strokeLinecap="round" />
                <path d="M153 168 C168 152 190 144 212 142" stroke="var(--color-brass)" strokeWidth="2.5" strokeLinecap="round" />
                <path d="M212 142 C232 146 248 156 258 168" stroke="var(--color-brass)" strokeWidth="2" strokeLinecap="round" />

                <path d="M72 150 C70 190 74 250 70 300" stroke="var(--color-brass)" strokeWidth="2" strokeLinecap="round" opacity="0.7" />
                <path d="M104 158 C106 200 100 255 104 300" stroke="var(--color-brass)" strokeWidth="1.8" strokeLinecap="round" opacity="0.6" />
                <path d="M198 156 C200 195 196 250 200 300" stroke="var(--color-brass)" strokeWidth="1.8" strokeLinecap="round" opacity="0.6" />
                <path d="M228 162 C226 200 232 255 228 300" stroke="var(--color-brass)" strokeWidth="2" strokeLinecap="round" opacity="0.7" />

                <path d="M60 300 C66 292 78 292 84 300" stroke="var(--color-brass)" strokeWidth="1.5" opacity="0.5" />
                <path d="M126 300 C136 288 166 288 176 300" stroke="var(--color-brass)" strokeWidth="1.5" opacity="0.5" />
            </g>

            <g stroke="var(--color-brass)" strokeLinecap="round">
                <line x1="258" y1="168" x2="258" y2="196" strokeWidth="1.5" />
                <circle cx="258" cy="200" r="4" strokeWidth="1.5" fill="none" />
                <line x1="228" y1="207" x2="288" y2="207" strokeWidth="2" />
                <line x1="230" y1="207" x2="230" y2="228" strokeWidth="1.2" />
                <line x1="286" y1="207" x2="286" y2="228" strokeWidth="1.2" />
                <path d="M219 228 C222 238 238 238 241 228 Z" strokeWidth="1.5" fill="var(--color-brass)" fillOpacity="0.25" />
                <path d="M275 228 C278 238 294 238 297 228 Z" strokeWidth="1.5" fill="var(--color-brass)" fillOpacity="0.25" />
            </g>

            <g stroke="#ffffff" strokeOpacity="0.55" strokeWidth="1.5" fill="#ffffff" fillOpacity="0.12">
                <circle cx="300" cy="247" r="6.5" />
                <path d="M300 254 C290 258 288 278 286 300 L314 300 C312 278 310 258 300 254 Z" strokeLinejoin="round" />
                <line x1="297" y1="257" x2="297" y2="266" strokeOpacity="0.8" />
                <line x1="303" y1="257" x2="303" y2="266" strokeOpacity="0.8" />
            </g>
            <g stroke="#ffffff" strokeOpacity="0.4" strokeWidth="1.5" fill="#ffffff" fillOpacity="0.08">
                <circle cx="326" cy="252" r="5.5" />
                <path d="M326 258 C318 262 316 280 315 300 L337 300 C336 280 334 262 326 258 Z" strokeLinejoin="round" />
            </g>

            <g stroke="var(--color-brass)" strokeWidth="2" strokeLinejoin="round" strokeLinecap="round">
                <path d="M435 96 L527 146 L343 146 Z" fill="var(--color-brass)" fillOpacity="0.08" />
                <rect x="355" y="146" width="160" height="14" fill="var(--color-brass)" fillOpacity="0.12" />

                <g strokeWidth="1.2">
                    <line x1="435" y1="112" x2="435" y2="132" />
                    <line x1="426" y1="117" x2="444" y2="117" />
                    <circle cx="426" cy="122" r="3" fill="none" />
                    <circle cx="444" cy="122" r="3" fill="none" />
                </g>

                <g fill="var(--color-brass)" fillOpacity="0.08">
                    <rect x="366" y="160" width="16" height="99" rx="2" />
                    <rect x="408" y="160" width="16" height="99" rx="2" />
                    <rect x="450" y="160" width="16" height="99" rx="2" />
                    <rect x="492" y="160" width="16" height="99" rx="2" />
                </g>
                <g strokeWidth="1.5">
                    <line x1="362" y1="160" x2="386" y2="160" />
                    <line x1="404" y1="160" x2="428" y2="160" />
                    <line x1="446" y1="160" x2="470" y2="160" />
                    <line x1="488" y1="160" x2="512" y2="160" />
                </g>

                <rect x="352" y="259" width="166" height="13" fill="var(--color-brass)" fillOpacity="0.1" />
                <rect x="342" y="272" width="186" height="13" fill="var(--color-brass)" fillOpacity="0.08" />
                <rect x="332" y="285" width="206" height="15" fill="var(--color-brass)" fillOpacity="0.06" />
            </g>
        </svg>
    );
}

export function BanyanSilhouette({ className }: ArtProps) {
    return (
        <svg viewBox="0 0 220 170" aria-hidden="true" className={className}>
            <g fill="currentColor">
                <ellipse cx="70" cy="62" rx="52" ry="34" />
                <ellipse cx="128" cy="46" rx="58" ry="36" />
                <ellipse cx="168" cy="72" rx="40" ry="26" />
                <ellipse cx="112" cy="74" rx="66" ry="30" />
                <path d="M104 150 C106 122 103 100 108 84 L116 84 C121 100 118 122 120 150 Z" />
                <path d="M52 88 C50 112 54 132 51 150 L57 150 C60 132 56 112 58 90 Z" />
                <path d="M158 92 C160 114 156 132 159 150 L165 150 C168 132 164 114 166 94 Z" />
                <path d="M82 86 C81 106 84 124 82 140 L86 140 C88 124 85 106 87 88 Z" />
                <rect x="20" y="150" width="180" height="3" rx="1.5" />
            </g>
        </svg>
    );
}
