=== Conditional Logic for Woo Product Add-ons ===
Contributors: meowcrew, freemius
Tags: Woocommerce Product Addons, product add-ons, woocommerce product options, woocommerce, WooCommerce product fields
Requires at least: 4.2
Tested up to: 6.3.0
Requires PHP: 7.0
Stable tag: 2.0.0
License: GNU General Public License v2
License URI: https://www.gnu.org/licenses/license-list.html#GPLCompatibleLicenses

Show or hide certain fields of the WooCommerce Product Addons based on other fields' values or states (eg, show field X when option Y is selected) or based on selected variations


== Description ==
Conditional Logic for WooCommerce Product Add-Ons is an extension to the official  **[WooCommerce Product Add-Ons plugin](https://woocommerce.com/products/product-add-ons/)**. With the help of this extension, you can set up conditional logic for Add-ons fields to either show or hide them based on what the user chooses, write or upload in other fields. Also you can determine to display add-ons fields only for specific product variations.

[youtube https://youtu.be/R6_Fj8WUDBs]

Important links:
**[Premium Version Page](https://meow-crew.com/plugin/conditional-logic-for-product-add-ons)** | **[Demo for Plugin Testing](http://conditional.meow-crew.com/demo/)** | **[Plugin Documentation](https://meow-crew.com/documentation/conditional-logic-for-woocommerce-product-add-ons-documentation)**

Conditional Logic is designed to work with any field of Product Add-ons, and each field can have its own rules. Fields for which you set conditions can be shown or hidden if Any or All requirements are met. Conditions - set of rules of what the user (your customer) should choose, write or define in other fields. Let's say you offer two types of Gift Wrap options (Free and paid), and you'd like to offer an 'Add a message' service to the Paid option only - then you set the 'Short text' field to be visible only when the customer chose the Paid option.
In the Premium version of the plugin you can also add conditions to **show Product Add-ons field only when user select specific Product Variation**. This feature can work alone or you can combine it with another conditions based on other add-ons fields output.

Types of conditions depend on the kind of field you use as the condition.

**Multiple choice and Checkboxes** types can be conditioned as:
Is checked - when the customer selects the needed option
Is not checked - when the customer selects or checks anything besides the specified option

**Short Text and Long Text** have the following condition types:
Is - text entered by a customer fully complies with your value
Is not - text entered by a customer is not the same as your value
Is empty - the customer does not enter any text
Is not empty - the customer enters any text
Text contains - text entered by the customer contains something specific anywhere in the text
Text does not contain - text entered by the customer does not contain what you specified anywhere in the text
Text starts with - text entered by the customer starts with anything specific
Text ends with - text entered by the customer ends with anything specific

**File upload** field type may have the following logic:
Is selected - if the customer has chosen a file to upload
Is not selected - if the customer has not chosen a file to upload yet

**Customer Defined Price and Quantity** are numeric types of fields, and their conditions may be:
Is - price entered by customer or selected quantity is equal to your value
Is not - price entered by customer or quantity is anything besides the value you set
Is greater than - price entered by customer or quantity is greater than the value
Is less than - price entered by customer or quantity is less than the value
Is greater than or equal - price entered by customer or quantity is greater than or equal to the value
Is less than or equal - price entered by customer or quantity is less than or equal to the value

Those conditions are available to you in the general add-ons section as well as on the product level.

Note: you should have **[WooCommerce Product Add-Ons plugin](https://woocommerce.com/products/product-add-ons/)** installed and configured.
You can find detailed instructions on how to hide and show WooCommerce Product Add-On options here in **[plugin's documentation](https://meow-crew.com/documentation/conditional-logic-for-woocommerce-product-add-ons-documentation)**

== Installation ==
1. Upload the plugin files to the \'/wp-content/plugins/conditional-logic-for-woo-product-add-ons\' directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the \'Plugins\' screen in WordPress
3. To design conditionals to your Add-ons' fields, go to products with addons (if you have them separately for each product) or to Products > Add-ons > choose Add-ons Group
5. Click on the 'Conditional logic' checkbox in the field for each you want to add conditions

== Frequently Asked Questions ==

= What addons plugins can be used along with this conditional logic? =
The Conditional Logic for WooCommerce Product Add-ons is designed to work only with official Product Add-ons. You won't be able to set up conditional logic without that plugin and with any other plugin of that type.

= How to show product add-ons field only when specific variant (e.g. red cap for red hoodie) is selected?
When you enable Conditional Logic for field, you will find Variations section. If you leave it empty - this field will be shown for every product variation. If you need to show the field only for specific variations - click on the field and select variations of the product, for which it will be displayed.

= Is there any type of field conditional logic cannot be applied to? =
Conditional logic can be applied to any default Product Add-ons field. As well as, all of the fields and their options can be used as conditionals.

= Are there any limits to the number of conditions set to one field? =
Yep, in community version it's limited to one condition. In premium version you can add as many conditions as you need.

= When trying to add the conditional logic, I see an error saying Please update (re-save) the post to set up conditional logic =
That means you have not yet updated the add-on or product. Conditions need to assign a special slug to each field and its options, and that happens only when you update the add-on or product after installing the plugin.

== Screenshots ==
1. How conditional fields work on product page
2. Premium feature: display field based on selected variant
3. How to add Conditional logic to add-ons field
4. Premium feature: show Product Add-ons fields only when specific variation selected
5. Dependencies for fields used as conditions
6. Two types - if all rules match or any (when condition works)
7. Condition types for Multiple choice and Checkboxes types of field
8. Condition types (relations) for text fields
9. Condition types for File upload type of field
10. Condition types for numeric types of fields

== Changelog ==
2023-07-26 - version 2.0.0
* New feature: Conditions based on selected product variation
* Updated: WooCommerce Product Add-ons version compatibility

2023-07-05 - version 1.2.1
* Updated Freemius SDK to the latest version

2023-06-17 - version 1.2.0
* New feature: All required fields will stay optional if those fields are hidden 
* HPOS compatibility

2023-04-20 - version 1.1.0
* WooCommerce Product Add-ons 6x version compatibility
* Minor UI improvements

2023-01-10 - version 1.0.0
* Initial release