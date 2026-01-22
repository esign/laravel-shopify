import React from 'react';
import { AppProvider, Page, Card, Text, BlockStack } from '@shopify/polaris';
import '@shopify/polaris/build/esm/styles.css';
import AppBridgeProvider from './AppBridgeProvider';

/**
 * Main App Component
 * 
 * This is a minimal starter component that demonstrates the basic setup
 * for a Shopify embedded app using Polaris and App Bridge.
 * 
 * Customize this component to build your app's homepage.
 */
export default function App() {
    return (
        <AppBridgeProvider>
            <AppProvider i18n={{}}>
                <Page title="Your Shopify App">
                    <BlockStack gap="400">
                        <Card>
                            <BlockStack gap="200">
                                <Text variant="headingMd" as="h2">
                                    Welcome to Your Shopify App
                                </Text>
                                <Text as="p">
                                    This is your embedded app home page. Customize this component 
                                    in <strong>resources/js/components/App.jsx</strong> to build your app's interface.
                                </Text>
                                <Text as="p" tone="subdued">
                                    Connected to shop: {window.shopifyConfig.shop || 'Not available'}
                                </Text>
                            </BlockStack>
                        </Card>
                    </BlockStack>
                </Page>
            </AppProvider>
        </AppBridgeProvider>
    );
}
