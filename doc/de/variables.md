
# Template variable format


* `$Varname`
* `$Varname->Property`    Die Variable ist ein Objekt auf dessen Eigenschaft
                          Property zugegriffen wird
* `$Varname->Method()`    Die Variable ist ein Objekt auf dessen Methode
                          Method() zugegriffen wird. Parameter können optional
                          in den Klammern angegeben werden.
* `$Varname.1`            Die Variable ist ein Array auf dessen Wert zugegriffen
                          werden soll der für den numerischen Key 1 definiert ist
* `$Varname.ArrayKey`     Die Variable ist ein Array auf dessen Wert zugegriffen
                          werden soll der für den Key 'ArrayKey' definiert ist
* `$Varname.$ArrayKey`    Die Variable ist ein Array auf dessen Wert zugegriffen
                          werden soll der für den Key $ArrayKey definiert ist.

Jeder Templatevariable können Filter-Funktionen zugeordnet werden, die genutzt
werden können um z.B. Sonderzeichen in HTML zu maskieren, u.ä.
Diese werden über das Pipe-Zeichen '|' getrennt definiert.

z.B. $Varname|escape


Zugriffe auf verschachtelte Elemente im Sinne einer Zugriffskette ist möglich.
Hier gelten die selben Einschränkungen die PHP auch hat.
