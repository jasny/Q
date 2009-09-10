<?php
echo '<?xml version="1.0" encoding="ISO-8859-1"?>
<!DOCTYPE article PUBLIC "-//OASIS//DTD Simplified DocBook XML V4.1.2.5//EN"
"http://www.oasis-open.org/docbook/xml/simple/4.1.2.5/sdocbook.dtd">
<article>
  <title>'.$data['title'].'</title>

  <section>
    <title>'.$data['section'].'</title>

    <para>'.$data['para'].'</para>
  </section>
</article>
';
?>