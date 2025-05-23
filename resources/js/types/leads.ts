export type Lead = {
    id: string;
    firstName: string;
    lastName: string;
    email: string;
    allowSendEmails: boolean;
    createdAt?: Date;
    updatedAt?: Date;
};
