
Sticky Panel View
=================

The sticky panel is used for display some elements in this panel when they leave window view port.
Sticky panel always visible, so elements that can be moved to panel will always be visible too.

How to Usage
------------
If you want add ability to show element on sticky panel - you should add `data-sticky` attribute to this element
```html
    <div id="flash-messages" class="notification" data-sticky></div>
```

Customization
--------------

**Add class to element in sticky panel**

Add `toggleClass` option to `data-sticky` attribute:
```html
    <div id="flash-messages" class="notification"
         data-sticky='{"toggleClass": "notification--medium"}'>
    </div>
```

**Add element placeholder to sticky panel**

Add placeholder using layout update:
```yaml
- '@add':
    id: sticky_element_notification
    parentId: sticky_panel_content
    blockType: container
```

Add placeholder template
```twig
{% block _sticky_element_notification_widget %}
    {% set attr = layout_attr_defaults(attr, {
        'id': 'sticky-element-notification'
    }) %}
    <div {{ block('block_attributes') }}></div>
{% endblock %}
```

Add `placeholderId` option to `data-sticky` attribute:
```html
    <div id="flash-messages" class="notification"
         data-sticky='{"placeholderId": "sticky-element-notification"}'>
    </div>
```

**Always show element in sticky panel**

Move element to sticky panel using layout update:
```yaml
- '@move':
    id: notification
    parentId: sticky_element_notification
```
