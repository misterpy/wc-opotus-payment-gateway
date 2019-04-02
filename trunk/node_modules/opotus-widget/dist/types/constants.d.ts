export interface IWidgetConfig {
    apiKey: string;
    secretKey: string;
    callbackUrl: string;
    amount: number;
    orderId: string;
    placeholderId: string;
    transactionDoneCallback: any;
}
export interface ITransactionPayload {
    transactionId: string;
    orderId: string;
    status: string;
    hash: string;
}
