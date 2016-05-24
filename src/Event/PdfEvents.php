<?php

namespace Drupal\entity_print\Event;

/**
 * The events related to PDF Engines.
 */
final class PdfEvents {

  /**
   * Name of the event fired when retrieving a PDF engine configuration.
   *
   * This event allows you to change the configuration of a PDF Engine
   * implementation right before the plugin manager creates the plugin instance.
   *
   * @Event
   *
   * @see \Symfony\Component\EventDispatcher\GenericEvent
   */
  const CONFIGURATION_ALTER = 'entity_print.pdf_engine.configuration_alter';

  /**
   * Name of the event fired right before the PDF is sent to the page.
   *
   * At this point, the HTML has been rendered and added as a page on the PDF
   * engine. The only thing left to happen is generate the filename and stream
   * the PDF data to the page.
   *
   * @Event
   *
   * @see \Drupal\entity_print\Event\PreSendPdfEvent
   */
  const PRE_SEND = 'entity_print.pdf_engine.pre_send';

  /**
   * The name of the event fired when rendering multiple entities onto one PDF.
   *
   * This event fires from the Views plugin when downloading multiple PDF's at
   * once.
   *
   * @Event
   *
   * @see \Drupal\entity_print\Event\PreSendPdfMultipleEvent
   * @see \Drupal\entity_print\Plugin\Action\PdfDownload
   */
  const PRE_SEND_MULTIPLE = 'entity_print.pdf_engine.pre_send_multiple';

  /**
   * Name of the event fired when building CSS assets.
   *
   * This event allows custom code to add their own CSS assets. Note the
   * recommended way is to manage CSS from your theme.
   * @link https://www.drupal.org/node/2430561#from-your-theme
   *
   * @code
   * $event->getBuild()['#attached']['library'][] = 'module/library';
   * @endcode
   *
   * @Event
   *
   * @see \Drupal\entity_print\Event\PdfCssAlterEvent
   */
  const CSS_ALTER = 'entity_print.pdf.css_alter';

  /**
   * This event is fired right after the HTML has been generated.
   *
   * Any manipulations to the HTML string can happen here. You should normally
   * avoid using this event and try and use the appropriate theme templates. We
   * currently use this event to fix a core bug with absolute URLs.
   *
   * @Event
   *
   * @see \Drupal\entity_print\Event\PdfHtmlAlterEvent
   */
  const POST_RENDER = 'entity_print.pdf.html_alter';

}
