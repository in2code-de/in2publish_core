# Upgrade

## Upgrade from  <=7.x to version 8.x

Please check your configuration for the setting 


> Ignore this fields for DIFF view
>
> ignoreFieldsForDifferenceView:
>
>   pages:
>
>     l18n_cfg


This default setting in version 7 (or older) prevents, that the page setting "hide default translation" is not published.
This is not intended. We recommend to remove this configuration.


