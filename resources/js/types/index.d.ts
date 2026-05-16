export type UserRole = 'admin' | 'commercial_manager' | 'sdr' | 'closer';

export interface User {
    id: number;
    name: string;
    email: string;
    role: UserRole;
    role_label: string;
    email_verified_at?: string;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User | null;
    };
};
