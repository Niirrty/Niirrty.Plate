Ein Integer: {{$int}}
Ein Float  : {{$float}}
Ein Boolean: {{if $bool}}TRUE{{else}}FALSE{{/if}}
{{+$Varname = 'Whatever 1'}}
TPL Var    : {{$Varname}}
{{* Single line comment *}}

Array 2

{{foreach from="$array2[ 0 ]" key=key value=value}}
{{$key}}: {{$value}}
{{/foreach}}

Array 1

{{for from=$array1 index=i step=1}}
{{if ($i % 2) === 0}}*{{else}}-{{/if}} {{$array1[ $i ]}}
{{/for}}

Datum + Zeit: {{$datetime->format('Y-m-d H.i:s')}}