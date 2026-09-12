export const SITE_NAME = 'AinPath';

export const SITE_NAME_BN = 'আইন পথ';

export const SITE_DESCRIPTION =
    'এলএলবি শিক্ষার্থীদের জন্য সেশন ও বিষয়ভিত্তিক সাজেশন, বই ও ক্লাস নোট — বিনামূল্যে।';

export const ADMIN_LOGIN_HREF = '/admin/login';

/**
 * The document title is resolved outside React, so it cannot read the shared
 * `site` prop through a hook. The entry point hands the name over here once the
 * first page lands, and the constants above stay as the fallback.
 */
let documentTitleName: string | null = null;

export function setDocumentTitleName(name: string | null | undefined): void {
    documentTitleName = name && name.trim() !== '' ? name : null;
}

export function documentTitleBase(): string {
    return documentTitleName ?? SITE_NAME;
}
