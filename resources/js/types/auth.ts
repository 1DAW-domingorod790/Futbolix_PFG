export type User = {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    avatar_url?: string | null;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    role: {
        id: number;
        name: string;
        [key: string]: unknown;
    };
    [key: string]: unknown;
};

export type Auth = {
    user: User;
    is_admin?: boolean;
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
