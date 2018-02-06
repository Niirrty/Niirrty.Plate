<?php extract( $this->data ); ?>
Ein Integer: <?= $int; ?> 
Ein Float  : <?= $float; ?> 
Ein Boolean: <?php if ( $bool ) { ?>TRUE<?php } else { ?>FALSE<?php } ?> 
<?php $Varname = 'Whatever 1'; ?> 
TPL Var    : <?= $Varname; ?> 

Array 2

<?php foreach( $array2[0] as $key => $value ) { ?>
<?= $key; ?>: <?= $value; ?> 
<?php } ?> 

<?php $this->includeWithCaching( $subTemplateFile, null ); ?> 
Array 1

<?php for( $i = 0, $c = count( $array1 ); $i < $c; $i++ ) { ?>
<?php if ( ($i % 2) === 0 ) { ?>*<?php } else { ?>-<?php } ?> <?= $array1[$i]; ?> 
<?php } ?> 

Datum + Zeit: <?= $datetime->format('Y-m-d H:i:s'); ?> 
