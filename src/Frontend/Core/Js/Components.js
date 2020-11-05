import { Ajax } from './Components/Ajax'
import { Modal } from './Components/Modal'
import { Cookiebar } from './Components/Cookiebar'
import { Locale } from './Components/Locale'
import { Controls } from './Components/Controls'
import { Facebook } from './Components/Facebook'
import { Forms } from './Components/Forms'
import { Statistics } from './Components/Statistics'
import { Twitter } from './Components/Twitter'

export class Components {
  initComponents () {
    this.ajax = new Ajax()
    this.modal = new Modal()
    this.cookiebar = new Cookiebar()
    this.locale = new Locale()
    this.controls = new Controls()
    this.facebook = new Facebook()
    this.forms = new Forms()
    this.statistics = new Statistics()
    this.twitter = new Twitter()
  }
}