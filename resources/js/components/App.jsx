import { createApp } from '@shopify/app-bridge';
import { useEffect } from 'preact/hooks';

/**
 * Main App Component
 * 
 * This is a minimal starter component that demonstrates the basic setup
 * for a Shopify embedded app using Preact and Polaris web components.
 * 
 */
export default function App() {
    return (
        <s-page heading="Your Shopify App">
            <s-section>
                <s-stack gap="base">
                    <s-heading level="2">Welcome to Your Shopify App</s-heading>
                    <s-paragraph>
                        This is your embedded app home page built with Preact and Shopify Polaris web components.
                        Customize this component in <strong>resources/js/components/App.jsx</strong> to build your app's interface.
                    </s-paragraph>
                    <s-paragraph tone="subdued">
                        Connected to shop: {window.shopifyConfig.shop || 'Not available'}
                    </s-paragraph>
                </s-stack>
            </s-section>
        </s-page>
    );
}
