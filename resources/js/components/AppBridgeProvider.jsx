import React from 'react';
import { Provider } from '@shopify/app-bridge-react';

export default function AppBridgeProvider({ children }) {
    const config = {
        apiKey: window.shopifyConfig.apiKey,
        host: window.shopifyConfig.host,
        forceRedirect: true,
    };

    return (
        <Provider config={config}>
            {children}
        </Provider>
    );
}
