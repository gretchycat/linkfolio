import { useState, useEffect } from '@wordpress/element';
import { registerBlockType } from '@wordpress/blocks';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';

registerBlockType('linkfolio/shortcode', {
    title: 'Linkfolio Links',
    icon: 'admin-links',
    category: 'widgets',
    attributes: {
        category: { type: 'string' }
    },
    edit({ attributes, setAttributes }) {
        const [categories, setCategories] = useState([
            { label: 'All', value: '' }
        ]);

        // Fetch categories from the backend (AJAX)
        useEffect(() => {
            wp.apiFetch({ path: '/linkfolio/v1/categories' }).then((result) => {
                setCategories([
                    { label: 'All', value: '' },
                    ...result.map(cat => ({ label: cat.name, value: cat.slug }))
                ]);
            });
        }, []);

        return (
            <>
                <InspectorControls>
                    <PanelBody title="Linkfolio Settings">
                        <SelectControl
                            label="Category"
                            value={attributes.category || ''}
                            options={categories}
                            onChange={(cat) => setAttributes({ category: cat })}
                        />
                    </PanelBody>
                </InspectorControls>
                <div>
                    <strong>Linkfolio Shortcode Preview:</strong>
                    <code>
                        {`[linkfolio${attributes.category ? ` category="${attributes.category}"` : ''}]`}
                    </code>
                </div>
            </>
        );
    },
    save() {
        // Block outputs just the shortcode for the frontend
        return null;
    }
});

