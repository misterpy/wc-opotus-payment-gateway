import { IWidgetConfig } from "./constants";
export declare class PaymentWidget {
    private opotusUrl;
    private requiredQueryParameters;
    private placeholderElement;
    private iframeContainerElement;
    private iframeElement;
    private widgetId;
    private config;
    init(config: IWidgetConfig): void;
    private generateIFrameUrl;
    private encodeQueryData;
    private getAmount;
    private processOpotusEvent;
    private emitTransactionResult;
    private resizeWidget;
}
