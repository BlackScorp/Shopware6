Feature: Payment success
  In order to pay with Mollie Payment methods
  As a customer
  I need to be able to add products into cart and complete the checkout

  Scenario Outline: Using Mollie Payment method
    Given following data "billingAddress":
      | key     | city      | zipcode | street          | countryId                        |
      | default | Test City | 1234567 | Test Street 123 | 0195722d704871489c1fa2db83ad96d8 |
    * i register with following data:
      | id   | accountType | firstName | lastName | email          | password     | billingAddress |
      | test | private     | Mollie    | Mollie   | test@mollie.nl | molliemollie | $default       |
    * iam logged in as with username "test@mollie.nl" and password "molliemollie"
    * i set billing country to "<country>"
    * i add "2" products "SWDEMO10005.1" to cart
    When i start checkout with payment "<paymentMethod>"
    Then iam on page "https://www.mollie.com/checkout/"
    * select status "<selectedStatus>"
    * iam on page "/checkout/finish"
    Examples:
      | paymentMethod         | country | selectedStatus |
      | PayPal | DE      | paid           |