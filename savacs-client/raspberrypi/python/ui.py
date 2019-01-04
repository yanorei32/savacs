# -*- coding:utf-8 -*-
import cv2
try:
    import pygtk
    pygtk.require('2.0')
except:
    pass
import numpy as np
import gtk
import gobject
import copy
import datetime
import threading
import pango
import socket
import os
import json
import time
import re
import requests
import subprocess
import sys
import ConfigParser
import logging
import traceback
import errno
import pprint
import copy

# TODO: JSONの取得に成功し、画像の取得に失敗すると、画像取得がリトライされない

class SubUI(object):
    def close_resources(self):
        pass

    def open_resources(self):
        pass

    def on_load(self):
        pass

    def get_root_object(self):
        return None

    def _seconds2txt(self, seconds):
        return '{:02d}:{:02d}'.format(
            int(seconds / 60),
            seconds % 60
        )

class RecordSendingUI(SubUI):
    def __init__(self, change_to, psc, sc):
        self._change_to = change_to
        self._psc = psc
        self._sc = sc

        self._main_hbox = gtk.HBox(spacing=2)

        self._label = gtk.Label()
        self._label.set_text('送信中...')
        self._label.modify_font(
            pango.FontDescription(psc.get_default_font() + ' 20')
        )
        self._main_hbox.pack_start(self._label, False, False)

    def get_root_object(self):
        return self._main_hbox

    def on_load(self):
        self._upload_thread = threading.Thread(target=self._record_voice_upload)
        self._upload_thread.setDaemon(True)
        self._upload_thread.start()

    def _record_voice_upload(self):
        self._sc.upload_record_voice(self._to_photostand_ids_array)
        self._change_to('PhotostandUI')

    def set_param(self, param):
        self._to_photostand_ids_array = param



class RecordSendConfirmUI(SubUI):
    def __init__(self, change_to, change_to_with_param, psc, logger):
        self._change_to = change_to
        self._change_to_with_param = change_to_with_param
        self._psc = psc
        self._logger = logger

        # TODO:
        temporary_measure_fix_overflow = -7

        self._main_vbox = gtk.VBox(spacing=5)

        self._confirm_message = gtk.Label()
        self._confirm_message.modify_font(
            pango.FontDescription(psc.get_default_font() + ' 50')
        )
        self._confirm_message.set_text(
            '録音を送信しますか？'
        )
        self._main_vbox.pack_start(self._confirm_message, False, False)

        self._sec_label = gtk.Label()
        self._sec_label.modify_font(
            pango.FontDescription(psc.get_default_font() + ' 100')
        )
        self._main_vbox.pack_start(self._sec_label, False, False)
        self._play_stop_hbuttonbox = gtk.HButtonBox()

        self._play_button = gtk.Button('再生')
        self._play_button.connect('clicked', self._on_play_button_click)
        self._play_button.set_size_request(
            204 + temporary_measure_fix_overflow, 64
        )
        self._play_button.get_child().modify_font(
            pango.FontDescription(psc.get_default_font() + ' 20')
        )
        self._play_stop_hbuttonbox.pack_start(self._play_button, False, False)

        self._stop_button = gtk.Button('停止')
        self._stop_button.connect('clicked', self._on_stop_button_click)
        self._stop_button.set_size_request(
            204 + temporary_measure_fix_overflow, 64
        )
        self._stop_button.get_child().modify_font(
            pango.FontDescription(psc.get_default_font() + ' 20')
        )
        self._play_stop_hbuttonbox.pack_start(self._stop_button, False, False)

        self._main_vbox.pack_start(self._play_stop_hbuttonbox, False, False)

        self._yes_no_hbuttonbox = gtk.HButtonBox()

        self._no_button = gtk.Button('録りなおす')
        self._no_button.connect('clicked', self._on_no_button_click)
        self._no_button.set_size_request(
            204 + temporary_measure_fix_overflow, 64
        )
        self._no_button.get_child().modify_font(
            pango.FontDescription(psc.get_default_font() + ' 20')
        )
        self._yes_no_hbuttonbox.pack_start(self._no_button, False, False)

        self._yes_button = gtk.Button('送信')
        self._yes_button.connect('clicked', self._on_yes_button_click)
        self._yes_button.set_size_request(
            204 + temporary_measure_fix_overflow, 64
        )
        self._yes_button.get_child().modify_font(
            pango.FontDescription(psc.get_default_font() + ' 20')
        )
        self._yes_no_hbuttonbox.pack_start(self._yes_button, False, False)

        self._main_vbox.pack_start(self._yes_no_hbuttonbox, False, False)


        self._statuses = {
            'normal'    : 0,
            'playing'   : 1,
        }
        self._status = self._statuses['normal']

        self._ffprobe_cli = \
            'ffprobe {} -show_streams -print_format json 2>/dev/null'.format(
                self._psc.get_capture_record_voice_file_name()
            )

        self._ffmpeg_cli = (
            'ffmpeg -i {} -vn -f s16le -ar 48k -ac 2 -' + \
            '| aplay -f dat -D hw:1'
        ).format(self._psc.get_capture_record_voice_file_name())

        pass

    def _change_to_record_ui(self):
        self._change_to('RecordUI')

    def _on_no_button_click(self, button):
        if self._status is self._statuses['playing']:
            self._on_play_end = self._change_to_record_ui
            self._ffmpeg_stop_request()
        else:
            self._change_to_record_ui()

    def _change_to_set_send_photostand_ids_ui(self):
        self._change_to_with_param(
            'SetSendPhotostandIDsUI',
            {
                'prev_ui_name': 'RecordSendConfirmUI',
                'next_ui_name': 'RecordSendingUI',
            }
        )

    def _on_yes_button_click(self, button):
        if self._status is self._statuses['playing']:
            self._on_play_end = self._change_to_set_send_photostand_ids_ui
            self._ffmpeg_stop_request()
        else:
            self._change_to_set_send_photostand_ids_ui()

    def _update_button_status(self):
        self._play_button.set_sensitive(
            self._status is self._statuses['normal']
        )
        self._stop_button.set_sensitive(
            self._status is self._statuses['playing']
        )

    def _ffmpeg_terminate_watcher(self):
        if self._ffmpeg_process.poll() is not None:
            self._status = self._statuses['normal']
            self._update_button_status()

            if self._on_play_end is not None:
                self._on_play_end()
            return False

        return True

    def _on_play_button_click(self, button):
        self._status = self._statuses['playing']
        self._update_button_status()

        self._on_play_end = None
        self._ffmpeg_process = subprocess.Popen(
            self._ffmpeg_cli,
            stdout  = subprocess.PIPE,
            stdin   = subprocess.PIPE,
            shell   = True
        )
        gobject.timeout_add(
            100,
            self._ffmpeg_terminate_watcher
        )

    def _ffmpeg_stop_request(self):
        out, err = self._ffmpeg_process.communicate('q')

    def _on_stop_button_click(self, button):
        self._on_play_end = None
        self._ffmpeg_stop_request()

    def open_resources(self):
        self._sec_label.set_text(
            self._seconds2txt(
                int(
                    round(
                        self._get_duration_by_recoded_file()
                    )
                )
            )
        )

    def _get_duration_by_recoded_file(self):
        p = subprocess.Popen(
            self._ffprobe_cli,
            stdout=subprocess.PIPE,
            stdin=subprocess.PIPE,
            shell=True,
        )

        out, err = p.communicate()

        decoded_data = None

        try:
            decoded_data = json.loads(out)

        except ValueError:
            self._logger.error('ffprobe returned invalid json.')
            return 0.0

        float_duration = None
        try:
            float_duration = float(
                decoded_data['streams'][0]['duration']
            )

        except KeyError:
            self._logger.error('stream information->0->duration not found.')
            return 0.0

        except ValueError:
            self._logger.error('ffprobe duration value error.')
            return 0.0

        return float_duration

    def get_root_object(self):
        return self._main_vbox

class RecordUI(SubUI):
    def __init__(self, change_to, psc, logger):
        self._change_to = change_to
        self._psc = psc
        self._logger = logger

        # TODO:
        temporary_measure_fix_overflow = -7

        self._seconds = 0

        self._main_vbox = gtk.VBox(spacing=5)

        self._status_label = gtk.Label()
        self._status_label.modify_font(
            pango.FontDescription(psc.get_default_font() + ' 50')
        )
        self._main_vbox.pack_start(self._status_label, False, False)

        self._sec_label = gtk.Label()
        self._sec_label.modify_font(
            pango.FontDescription(psc.get_default_font() + ' 100')
        )
        self._main_vbox.pack_start(self._sec_label, False, False)

        self._rec_hbox = gtk.HButtonBox()

        self._rec_start_button = gtk.Button('録音開始')
        self._rec_start_button.connect(
            'clicked', self._on_rec_start_button_click
        )
        self._rec_start_button.set_size_request(
            204 + temporary_measure_fix_overflow, 64
        )
        self._rec_start_button.get_child().modify_font(
            pango.FontDescription(psc.get_default_font() + ' 20')
        )

        self._rec_hbox.pack_start(self._rec_start_button, False, False)

        self._rec_stop_button = gtk.Button('録音停止')
        self._rec_stop_button.connect(
            'clicked', self._on_rec_stop_button_click
        )
        self._rec_stop_button.set_size_request(
            204 + temporary_measure_fix_overflow, 64
        )
        self._rec_stop_button.get_child().modify_font(
            pango.FontDescription(psc.get_default_font() + ' 20')
        )

        self._rec_hbox.pack_start(self._rec_stop_button, False, False)
        self._main_vbox.pack_start(self._rec_hbox)

        self._back_hbox = gtk.HButtonBox()
        self._back_button = gtk.Button('戻る')
        self._back_button.connect('clicked', self._on_back_button_clicked)
        self._back_button.set_size_request(
            204 + temporary_measure_fix_overflow, 64
        )
        self._back_button.get_child().modify_font(
            pango.FontDescription(psc.get_default_font() + ' 20')
        )
        self._back_hbox.pack_start(self._back_button, False, False)
        self._main_vbox.pack_start(self._back_hbox, False, False)

        self._statuses = {
            'wait':         '録音開始待ち',
            'recording':    '録音中',
        }

        self._ffmpeg_cli = (
            'ffmpeg -f alsa -ac 1 -i hw:1 -y {} 2>/dev/null'
        ).format(self._psc.get_capture_record_voice_file_name())
        self._status = self._statuses['wait']

        self._next_ui_name = ''
        self._max_record_sec = 3 * 60 * 1000

    def _ffmpeg_terminate_watcher(self):
        if self._ffmpeg_process.poll() is not None:
            self._status = self._statuses['wait']
            gobject.source_remove(self._time_count_up_gobject_id)
            self._elements_status_update()
            self._change_to(self._next_ui_name)
            return False

        return True

    def _timer_update(self):
        self._sec_label.set_text(
            self._seconds2txt(self._seconds)
        )

    def _time_count_up(self):
        self._seconds += 1
        if self._seconds >= self._max_record_sec:
            self._ffmpeg_stop_request()
            self._next_ui_name = 'RecordSendConfirmUI'
            return False

        self._timer_update()
        return True

    def _on_rec_start_button_click(self, button):
        self._status = self._statuses['recording']
        self._elements_status_update()

        self._ffmpeg_process = subprocess.Popen(
            self._ffmpeg_cli,
            stdout  = subprocess.PIPE,
            stdin   = subprocess.PIPE,
            shell   = True
        )

        gobject.timeout_add(
            100,
            self._ffmpeg_terminate_watcher
        )

        self._time_count_up_gobject_id = gobject.timeout_add(
            1000,
            self._time_count_up
        )

    def _ffmpeg_stop_request(self):
        out, err = self._ffmpeg_process.communicate('q')

    def _on_rec_stop_button_click(self, button):
        self._next_ui_name = 'RecordSendConfirmUI'
        self._ffmpeg_stop_request()

    def _on_back_button_clicked(self, button):
        if self._status is not self._statuses['wait']:
            self._ffmpeg_stop_request()
            self._next_ui_name = 'PhotostandUI'
        else:
            self._change_to('PhotostandUI')

    def _elements_status_update(self):
        self._status_label.set_text(self._status)

        self._rec_start_button.set_sensitive(
            self._status is self._statuses['wait']
        )

        self._rec_stop_button.set_sensitive(
            self._status is self._statuses['recording']
        )

    def open_resources(self):
        self._seconds = 0
        self._timer_update()
        self._status = self._statuses['wait']
        self._elements_status_update()

    def get_root_object(self):
        return self._main_vbox

class PlayVoiceUI(SubUI):
    def __init__(self, change_to, psc, sc, logger):
        self._change_to = change_to
        self._logger = logger
        self._psc = psc
        self._sc = sc

        self._main_hbox = gtk.HBox(spacing=2)

        self._scrolled_window = gtk.ScrolledWindow()
        self._scrolled_window.set_policy(
            gtk.POLICY_AUTOMATIC, gtk.POLICY_AUTOMATIC
        )

        # ClientId, Date, Length, URI(nothing column)
        self._list_store = gtk.ListStore(str, str, str, str)
        self._tree_view = gtk.TreeView(self._list_store)

        renderer_text = gtk.CellRendererText()
        column = gtk.TreeViewColumn('ClientId', renderer_text, text=0)
        column.set_sort_column_id(0)
        self._tree_view.append_column(column)

        renderer_text = gtk.CellRendererText()
        column = gtk.TreeViewColumn('Date', renderer_text, text=1)
        column.set_sort_column_id(1)
        self._tree_view.append_column(column)

        renderer_text = gtk.CellRendererText()
        renderer_text.set_alignment(1.0, 0.0)
        column = gtk.TreeViewColumn('Length', renderer_text, text=2)
        column.set_sort_column_id(2)
        self._tree_view.append_column(column)

        self._tree_view.modify_font(
            pango.FontDescription(psc.get_default_font() + ' 20')
        )
        self._tree_view.set_size_request(584, 438)

        self._scrolled_window.add(self._tree_view)
        self._main_hbox.pack_start(self._scrolled_window, False, False)

        self._vbutton_box = gtk.VButtonBox()

        # TODO:
        temporary_measure_fix_overflow = -7

        self._play_button = gtk.Button('再生')
        self._play_button.connect('clicked', self._on_play_button_clicked)
        self._play_button.set_size_request(
            204 + temporary_measure_fix_overflow, 64
        )
        self._play_button.get_child().modify_font(
            pango.FontDescription(psc.get_default_font() + ' 20')
        )
        self._vbutton_box.pack_start(self._play_button, False, False)

        self._stop_button = gtk.Button('停止')
        self._stop_button.connect('clicked', self._on_stop_button_clicked)
        self._stop_button.set_size_request(
            204 + temporary_measure_fix_overflow, 64
        )
        self._stop_button.get_child().modify_font(
            pango.FontDescription(psc.get_default_font() + ' 20')
        )
        self._vbutton_box.pack_start(self._stop_button, False, False)

        self._back_button = gtk.Button('戻る')
        self._back_button.connect('clicked', self._on_back_button_clicked)
        self._back_button.set_size_request(
            204 + temporary_measure_fix_overflow, 64
        )
        self._back_button.get_child().modify_font(
            pango.FontDescription(psc.get_default_font() + ' 20')
        )
        self._vbutton_box.pack_start(self._back_button, False, False)

        self._main_hbox.pack_start(self._vbutton_box, False, False)

        self._statuses = {
            'wait'          : 0,
            'downloading'   : 1,
            'playing'       : 2,
        }
        self._status = self._statuses['wait']

        self._ffmpeg_cli = (
            'ffmpeg -i {} -vn -f s16le -ar 48k -ac 2 -' + \
            '| aplay -f dat -D hw:1'
        ).format(self._psc.get_download_record_voice_file_name())

    def _elements_status_update(self):
        self._play_button.set_sensitive(
            self._status is self._statuses['wait']
        )
        self._tree_view.set_sensitive(
            self._status is self._statuses['wait']
        )
        self._back_button.set_sensitive(
            self._status is not self._statuses['downloading']
        )
        self._stop_button.set_sensitive(
            self._status is self._statuses['playing']
        )

    def _ffmpeg_stop_req(self):
        out, err = self._ffmpeg_process.communicate('q')

    def on_load(self):
        voice_dictionary = self._sc.get_resentry_record_voices_object()

        self._list_store.clear()

        for voice in voice_dictionary:
            self._list_store.append([
                voice['send_from'],
                voice['created_at'],
                voice['duration'],
                voice['uri'],
            ])

        self._elements_status_update()

    def _on_stop_button_clicked(self, button):
        if self._status is self._statuses['playing']:
            self._ffmpeg_stop_req()

    def _aac_download(self, uri):
        self._aac_download_thread_ret = self._sc.download_aac(uri)

    def _ffmpeg_terminate_watcher(self):
        if self._status != self._statuses['playing']:
            return False

        if self._ffmpeg_process.poll() is not None:
            self._status = self._statuses['wait']
            self._elements_status_update()

            return False

        return True


    def _aac_download_complete_watcher(self):
        if self._download_thread.isAlive():
            return True

        if self._aac_download_thread_ret is False:
            self._status = self._status['wait']
            self._elements_status_update()
            return False

        self._status = self._statuses['playing']
        self._elements_status_update()

        self._ffmpeg_process = subprocess.Popen(
            self._ffmpeg_cli,
            stdout  = subprocess.PIPE,
            stdin   = subprocess.PIPE,
            shell   = True
        )

        gobject.timeout_add(
            100,
            self._ffmpeg_terminate_watcher
        )

    def _on_play_button_clicked(self, button):
        (model, tree_iterator) = self._tree_view.get_selection().get_selected()

        if tree_iterator is None:
            self._logger.warn('Selected auido not found.')
            return

        self._status = self._statuses['downloading']
        self._elements_status_update()

        self._download_thread = threading.Thread(
            target=self._aac_download,
            args=( model[tree_iterator][3], )
        )
        self._download_thread.setDaemon(True)
        self._download_thread.start()

        gobject.timeout_add(
            100,
            self._aac_download_complete_watcher
        )

    def _on_back_button_clicked(self, button):
        if self._status is self._statuses['playing']:
            self._ffmpeg_stop_req()

        self._change_to('PhotostandUI')

    def get_root_object(self):
        return self._main_hbox

class ImageSendingUI(SubUI):
    def __init__(self, change_to, psc, sc):
        self._change_to = change_to
        self._psc = psc
        self._sc = sc

        self._main_hbox = gtk.HBox(spacing=2)

        self._label = gtk.Label()
        self._label.set_text('送信中...')
        self._label.modify_font(
            pango.FontDescription(psc.get_default_font() + ' 20')
        )
        self._main_hbox.pack_start(self._label, False, False)

    def get_root_object(self):
        return self._main_hbox

    def on_load(self):
        self._upload_thread = threading.Thread(target=self._image_upload)
        self._upload_thread.setDaemon(True)
        self._upload_thread.start()

    def _image_upload(self):
        self._sc.upload_selfy_image(self._to_photostand_ids_array)
        self._change_to('PhotostandUI')

    def set_param(self, param):
        self._to_photostand_ids_array = param

class SetSendPhotostandIDsUI(SubUI):
    def __init__(self, change_to_with_param, psc, sc, logger, change_to):
        self._sc = sc
        self._psc = psc
        self._logger = logger
        self._change_to_with_param = change_to_with_param
        self._change_to = change_to

        self._main_hbox = gtk.HBox(spacing=2)

        self._scrolled_window = gtk.ScrolledWindow()
        self._scrolled_window.set_policy(
            gtk.POLICY_AUTOMATIC,
            gtk.POLICY_AUTOMATIC
        )

        self._list_store = gtk.ListStore(bool, str)
        self._tree_view = gtk.TreeView(model=self._list_store)
        self._tree_view.modify_font(
            pango.FontDescription(psc.get_default_font() + ' 20')
        )

        renderer_toggle = gtk.CellRendererToggle()
        renderer_toggle.connect('toggled', self._on_cell_toggled)
        column = gtk.TreeViewColumn('Send?', renderer_toggle, active=0)
        self._tree_view.append_column(column)

        renderer_text = gtk.CellRendererText()
        column = gtk.TreeViewColumn('ID', renderer_text, text=1)
        self._tree_view.append_column(column)

        self._tree_view.set_size_request(584, 438)
        self._tree_view.get_selection().set_mode(gtk.SELECTION_NONE)
        self._tree_view.set_can_focus(False)

        self._scrolled_window.add(self._tree_view)
        self._main_hbox.pack_start(self._scrolled_window, False, False)

        self._vbuttonbox = gtk.VButtonBox()

        # TODO:
        temporary_measure_fix_overflow = -7

        self._ok_button = gtk.Button('OK')
        self._ok_button.connect('clicked', self._on_ok_button_clicked)
        self._ok_button.set_size_request(
            204 + temporary_measure_fix_overflow, 64
        )
        self._ok_button.get_child().modify_font(
            pango.FontDescription(psc.get_default_font() + ' 20')
        )
        self._vbuttonbox.pack_start(self._ok_button, False, False)

        self._back_button= gtk.Button('戻る')
        self._back_button.connect('clicked', self._on_back_button_clicked)
        self._back_button.set_size_request(
            204 + temporary_measure_fix_overflow, 64
        )
        self._back_button.get_child().modify_font(
            pango.FontDescription(psc.get_default_font() + ' 20')
        )
        self._vbuttonbox.pack_start(self._back_button, False, False)

        self._main_hbox.pack_start(self._vbuttonbox, False, False)

        self._next_ui_name = None

    def get_root_object(self):
        return self._main_hbox

    def _on_back_button_clicked(self, button):
        self._change_to(self._prev_ui_name)

    def _on_ok_button_clicked(self, button):
        to_photostand_ids_array = []

        for row in self._list_store:
            if row[0]:
                to_photostand_ids_array.append(int(row[1]))

        self._change_to_with_param(self._next_ui_name, to_photostand_ids_array)

    def _update_ok_button_sensitive(self):
        isOKButtonActive = False

        for row in self._list_store:
            isOKButtonActive = isOKButtonActive or row[0]

        self._ok_button.set_sensitive(isOKButtonActive)

    def _on_cell_toggled(self, widget, idx):
        self._list_store[idx][0] = not self._list_store[idx][0]
        self._update_ok_button_sensitive()


    def on_load(self):
        photostands = self._sc.get_associated_photostands_array()
        photostands_length = len(photostands)


        self._list_store.clear()
        for photostand in photostands:
            self._list_store.append([True, str(photostand)])

        if photostands_length is 0:
            # TODO: Associated photostands count is 0 Error
            pass

        elif photostands_length is 1:
            self._on_ok_button_clicked(None)
            return
            #pass

        self._update_ok_button_sensitive()

    def set_param(self, datas):
        self._prev_ui_name = datas['prev_ui_name']
        self._next_ui_name = datas['next_ui_name']

class ImageSendConfirmUI(SubUI):
    def __init__(self, change_to, psc, change_to_with_param):
        self._change_to = change_to
        self._change_to_with_param = change_to_with_param
        self._psc = psc

        self._main_hbox = gtk.HBox(spacing=2)

        self._image = gtk.Image()
        self._main_hbox.pack_start(self._image, False, False)

        # TODO:
        temporary_measure_fix_overflow = -7

        self._vbuttonbox = gtk.VButtonBox()

        self._send_button = gtk.Button('送信する')
        self._send_button.set_size_request(
            204 + temporary_measure_fix_overflow, 64
        )
        self._send_button.get_child().modify_font(
            pango.FontDescription(psc.get_default_font() + ' 20')
        )
        self._send_button.connect('clicked', self._on_send_button_clicked)
        self._vbuttonbox.pack_start(self._send_button, False, False)

        self._cancel_button = gtk.Button('撮り直す')
        self._cancel_button.set_size_request(
            204 + temporary_measure_fix_overflow, 64
        )
        self._cancel_button.get_child().modify_font(
            pango.FontDescription(psc.get_default_font() + ' 20')
        )
        self._cancel_button.connect('clicked', self._on_cancel_button_clicked)
        self._vbuttonbox.pack_start(self._cancel_button, False, False)

        self._main_hbox.pack_start(self._vbuttonbox, False, False)

    def open_resources(self):
        self._image.set_from_file(
            self._psc.get_capture_selfy_image_file_name(),
        )

    def _on_cancel_button_clicked(self, button):
        os.unlink(
            self._psc.get_capture_selfy_image_file_name(),
        )
        self._change_to('CaptureUI')

    def _on_send_button_clicked(self, button):
        self._change_to_with_param(
            'SetSendPhotostandIDsUI',
            {
                'next_ui_name': 'ImageSendingUI',
                'prev_ui_name': 'ImageSendConfirmUI'
            }
        )

    def get_root_object(self):
        return self._main_hbox

class ShutterUI(SubUI):
    def __init__(self, change_to):
        self._change_to = change_to

        self._main_hbox = gtk.HBox(spacing=2)

        self._image = gtk.Image()
        self._image.set_from_pixbuf(
            gtk.gdk.pixbuf_new_from_array(
                np.tile(np.uint8([0, 0, 0]), (438, 584, 1)),
                gtk.gdk.COLORSPACE_RGB, 8
            )
        )

        self._main_hbox.pack_start(self._image, False, False)

    def _next_ui(self):
        self._change_to('ImageSendConfirmUI')

    def on_load(self):
        gobject.timeout_add(100, self._next_ui)

    def get_root_object(self):
        return self._main_hbox

class CaptureUI(SubUI):
    def __init__(self, change_to, psc, logger):
        self._change_to = change_to
        self._psc = psc
        self._logger = logger

        self._main_hbox = gtk.HBox(spacing=2)

        self._image = gtk.Image()
        self._image.set_from_pixbuf(
            gtk.gdk.pixbuf_new_from_array(
                np.tile(np.uint8([0, 0, 0]), (438, 584, 1)),
                gtk.gdk.COLORSPACE_RGB, 8
            )
        )
        self._main_hbox.pack_start(self._image, False, False)

        # TODO:
        temporary_measure_fix_overflow = -7

        self._vbuttonbox = gtk.VButtonBox()

        self._shutter_button = gtk.Button('撮影')
        self._shutter_button.set_size_request(
            204 + temporary_measure_fix_overflow,
            64
        )
        self._shutter_button.get_child().modify_font(
            pango.FontDescription(psc.get_default_font() + ' 20')
        )
        self._shutter_button.connect(
            'clicked',
            self._on_shutter_button_clicked
        )
        self._vbuttonbox.pack_start(self._shutter_button, False, False)

        self._back_to_photostand_button = gtk.Button(u'戻る')
        self._back_to_photostand_button.set_size_request(
            204 + temporary_measure_fix_overflow,
            64
        )
        self._back_to_photostand_button.get_child().modify_font(
            pango.FontDescription(psc.get_default_font() + ' 20')
        )
        self._back_to_photostand_button.connect(
            'clicked',
            self._on_back_to_photostand_button_clicked
        )
        self._vbuttonbox.pack_start(
            self._back_to_photostand_button, False, False
        )

        self._main_hbox.pack_start(self._vbuttonbox, False, False)

        self._cam = None
        self._last_frame = None
        self._refresh_timeout_id = None
        self._last_frame_lock = threading.Lock() # for save image

    def _on_shutter_button_clicked(self, button):
        self._logger.info('Image write to disk')

        with self._last_frame_lock:
            cv2.imwrite(
                self._psc.get_capture_selfy_image_file_name(),
                self._last_frame
            )

        self._change_to('ShutterUI')

    def _on_back_to_photostand_button_clicked(self, button):
        self._change_to('PhotostandUI')

    def _refresh_frame(self):
        _, new_frame = self._cam.read()
        new_frame = cv2.resize(new_frame, (584, 438))

        self._image.set_from_pixbuf(
            gtk.gdk.pixbuf_new_from_array(
                cv2.cvtColor(
                    cv2.flip(new_frame, 1),
                    cv2.COLOR_BGR2RGB
                ),
                gtk.gdk.COLORSPACE_RGB,
                8
            )
        )

        self._image.show_all()

        with self._last_frame_lock:
            self._last_frame = new_frame

        return True

    def open_resources(self):
        self._cam = cv2.VideoCapture(2)

        framerate = self._cam.get(cv2.CAP_PROP_FPS)

        if framerate == 0.0:
            self._logger.error('Camera fps is 0.0')
            self._cam.release()
            return False

        refresh_rate_ms = int(
            round(1000.0 / framerate)
        )

        self._refresh_timeout_id = gobject.timeout_add(
            refresh_rate_ms,
            self._refresh_frame
        )

        return True

    def close_resources(self):
        if self._refresh_timeout_id is not None:
            gobject.source_remove(self._refresh_timeout_id)
            self._refresh_timeout_id = None

        if self._cam:
            self._cam.release()

    def get_root_object(self):
        return self._main_hbox

class PhotostandUI(SubUI):
    def __init__(self, change_to, psc, sc):
        self._change_to = change_to
        self._psc = psc
        self._sc = sc
        self._last_index = -1


        self._main_hbox = gtk.HBox(spacing=2)

        self._image = gtk.Image()
        self._image.set_from_pixbuf(
            gtk.gdk.pixbuf_new_from_array(
                np.tile(
                    np.uint8([0, 127, 127]),
                    (438, 584, 1)
                ),
                gtk.gdk.COLORSPACE_RGB,
                8
            )
        )
        self._main_hbox.pack_start(self._image, False, False)

        self._vbuttonbox = gtk.VButtonBox()

        # TODO:
        temporary_measure_fix_overflow = -7

        self._change_to_capture_ui_button = gtk.Button('撮影画面へ')
        self._change_to_capture_ui_button.get_child().modify_font(
            pango.FontDescription(psc.get_default_font() + ' 20')
        )
        self._change_to_capture_ui_button.set_size_request(
            204 + temporary_measure_fix_overflow, 64
        )
        self._change_to_capture_ui_button.connect(
            'clicked',
            self._on_change_ui_buttons_clicked,
            'CaptureUI'
        )
        self._vbuttonbox.pack_start(
            self._change_to_capture_ui_button, False, False
        )

        self._change_to_record_ui_button = gtk.Button('録音画面へ')
        self._change_to_record_ui_button.get_child().modify_font(
            pango.FontDescription(psc.get_default_font() + ' 20')
        )
        self._change_to_record_ui_button.set_size_request(
            204 + temporary_measure_fix_overflow, 64
        )
        self._change_to_record_ui_button.connect(
            'clicked',
            self._on_change_ui_buttons_clicked,
            'RecordUI'
        )
        self._vbuttonbox.pack_start(
            self._change_to_record_ui_button, False, False
        )

        self._change_to_play_voice_ui_button = gtk.Button('再生画面へ')
        self._change_to_play_voice_ui_button.get_child().modify_font(
            pango.FontDescription(psc.get_default_font() + ' 20')
        )
        self._change_to_play_voice_ui_button.set_size_request(
            204 + temporary_measure_fix_overflow, 64
        )
        self._change_to_play_voice_ui_button.connect(
            'clicked',
            self._on_change_ui_buttons_clicked,
            'PlayVoiceUI'
        )
        self._vbuttonbox.pack_start(
            self._change_to_play_voice_ui_button, False, False
        )

        self._main_hbox.pack_start(self._vbuttonbox, False, False)

        self._update_image()
        gobject.timeout_add(
            1500,
            self._update_image
        )

    def _update_image(self):
        json_obj = self._sc.get_last_selfy_image_object()

        if len(json_obj) is 0:
            self._image.set_from_pixbuf(
                gtk.gdk.pixbuf_new_from_array(
                    np.tile(
                        np.uint8([192, 0, 0]),
                        (438, 584, 1)
                    ),
                    gtk.gdk.COLORSPACE_RGB,
                    8
                )
            )
            return True

        self._last_index += 1

        if len(json_obj) <= self._last_index:
            self._last_index = 0

        d = json_obj[json_obj.keys()[self._last_index]]

        if d['status'] is 1:
            self._image.set_from_pixbuf(
                json_obj[json_obj.keys()[self._last_index]]['pixbuf']
            )
        else:
            self._image.set_from_pixbuf(
                gtk.gdk.pixbuf_new_from_array(
                    np.tile(
                        np.uint8([0, 0, 192]),
                        (438, 584, 1)
                    ),
                    gtk.gdk.COLORSPACE_RGB,
                    8
                )
            )

        return True


    def _on_change_ui_buttons_clicked(self, button, ui_name):
        self._change_to(ui_name)

    def get_root_object(self):
        return self._main_hbox


class InitializeServerConnectInstanceUI(SubUI):
    def __init__(self, change_to, psc):
        self._change_to = change_to

        self._main_hbox = gtk.HBox(spacing=2)

        self._main_label = gtk.Label()
        self._main_label.set_text(
            '初期データをサーバーから受信しています。'
        )
        self._main_label.modify_font(
            pango.FontDescription(psc.get_default_font() + ' 20')
        )
        self._sub_label = gtk.Label()
        self._sub_label.set_text(
            'この状態が1分以上続く場合は報告してください。'
        )
        self._sub_label.modify_font(
            pango.FontDescription(psc.get_default_font() + ' 15')
        )
        self._main_vbox = gtk.VBox(spacing=2)
        self._main_vbox.pack_start(self._main_label, False, False)
        self._main_vbox.pack_start(self._sub_label, False, False)

    def get_root_object(self):
        return self._main_vbox

class ReadyUI(SubUI):
    def __init__(self, change_to, psc):
        self._change_to = change_to

        self._main_hbox = gtk.HBox(spacing=2)

        self._main_label = gtk.Label()
        self._main_label.set_text(
            'Ready.'
        )
        self._main_label.modify_font(
            pango.FontDescription(psc.get_default_font() + ' 20')
        )
        self._main_vbox = gtk.VBox(spacing=2)
        self._main_vbox.pack_start(self._main_label, False, False)

    def get_root_object(self):
        return self._main_vbox

class UIManager(object):
    def __init__(self, window, psc, logger):
        self._logger = logger
        self._window = window
        self._psc = psc
        self._sc = None

        self._ui_instances = {
            'InitializeServerConnectInstanceUI':
                InitializeServerConnectInstanceUI(
                    self._change_to, self._psc
                ),
            'ReadyUI':
                ReadyUI(
                    self._change_to, self._psc
                ),
            'CaptureUI':
                CaptureUI(
                    self._change_to, self._psc, self._logger
                ),
            'ShutterUI':
                ShutterUI(
                    self._change_to
                ),
            'ImageSendConfirmUI':
                ImageSendConfirmUI(
                    self._change_to, self._psc, self._change_to_with_param
                ),
            'RecordUI':
                RecordUI(
                    self._change_to, self._psc, self._logger
                ),
            'RecordSendConfirmUI':
                RecordSendConfirmUI(
                    self._change_to, self._change_to_with_param, self._psc, self._logger
                ),
        }

        self._last_ui = None
        self._change_to('InitializeServerConnectInstanceUI')

        self._initialization_thread = threading.Thread(
            target=self._server_connection_initialization
        )
        self._initialization_thread.setDaemon(True)
        self._initialization_thread.start()

    def _server_connection_initialization(self):
        self._sc = ServerConnection(self._psc, self._logger)

        self._ui_instances['PhotostandUI'] = PhotostandUI(
            self._change_to, self._psc, self._sc
        )

        self._ui_instances['ImageSendingUI'] = ImageSendingUI(
            self._change_to, self._psc, self._sc
        )
        self._ui_instances['RecordSendingUI'] = RecordSendingUI(
            self._change_to, self._psc, self._sc
        )
        self._ui_instances['SetSendPhotostandIDsUI'] = \
            SetSendPhotostandIDsUI(
                self._change_to_with_param, self._psc, self._sc, self._logger, self._change_to
            )
        self._ui_instances['PlayVoiceUI'] = \
            PlayVoiceUI(
                self._change_to, self._psc, self._sc, self._logger
            )

        self._change_to('PhotostandUI')

    def _change_to(self, name):
        if not self._ui_instances.has_key(name):
            self._logger.critical('UI Not found: ' + name)
            return

        if self._last_ui is not None:
            old_ui = self._ui_instances[self._last_ui]

            old_ui.close_resources()

            self._window.remove(old_ui.get_root_object())


        self._logger.info('Change UI from {} to {}'.format(self._last_ui, name))
        new_ui = self._ui_instances[name]
        new_ui.open_resources()
        self._window.add(new_ui.get_root_object())
        self._last_ui = name
        self._window.show_all()
        new_ui.on_load()

    def _change_to_with_param(self, name, param):
        if not self._ui_instances.has_key(name):
            self._logger.critical('UI Not found: ' + name)
            return

        if self._last_ui is not None:
            old_ui = self._ui_instances[self._last_ui]

            old_ui.close_resources()

            self._window.remove(old_ui.get_root_object())


        self._logger.info('Change UI With Param from {} to {}'.format(self._last_ui, name))
        new_ui = self._ui_instances[name]
        new_ui.set_param(param)
        new_ui.open_resources()
        self._window.add(new_ui.get_root_object())
        self._last_ui = name
        self._window.show_all()
        new_ui.on_load()



class InfoBar(object):
    def __init__(self, parent_frame, psc, logger):
        self._psc = psc
        self._logger = logger

        self._info_label = gtk.Label()

        self._info_label.modify_font(
            pango.FontDescription(psc.get_default_font() + ' 20')
        )

        self._refresh()

        # auto refresh (interval: 1000ms)
        gobject.timeout_add(
            1000,
            self._refresh
        )

        parent_frame.add(
            self._info_label
        )

    def _get_temperature(self):
        file_name = self._psc.get_sensor_daemon_socket_file_name()

        if not os.path.exists(file_name):
            self._logger.warn('Socket file not exists')
            return '{SOCKET FILE NOT EXISTS}'

        json_data = None

        try:
            s = socket.socket(
                socket.AF_UNIX,
                socket.SOCK_STREAM
            )

            s.connect(file_name)
            s.send('get: temperature')
            json_data = s.recv(1024)
            s.close()

        except socket.error as e:
            self._logger.exception('Socket Error: %s', e)
            return '{SOCKET ERROR}'

        except socket.timeout as e:
            self._logger.error('Socket timeout')
            return '{SOCKET TIMEOUT}'

        decoded_data = None

        try:
            decoded_data = json.loads(json_data)

        except ValueError:
            self._logger.erorr(
                'Sensor json_data is not valid: ' + json_data
            )
            return '{API ERROR}'

        if decoded_data['status'] != 'success':
            self._logger.error(
                'Sensor status is ' + decoded_data['status_description']
            )

            return decoded_data['status_description']

        return '%.1f' % decoded_data['value'] + '℃'

    def _refresh(self):
        clock = datetime.datetime.today().strftime('%Y-%m-%d %H:%M:%S')

        if self._psc.get_sensor_is_active():
            temperature = self._get_temperature()

            self._info_label.set_text(
                clock + ' ' * 5 + temperature
            )
        else:
            self._info_label.set_text(clock)

        return True


class MainWindow(object):
    def __init__(self, psc, logger):
        self._window = gtk.Window()
        self._window.set_border_width(5)
        self._window.set_title('SAVACS UI')
        self._window.set_size_request(800, 480)
        self._window.set_resizable(False)
        self._window.connect('destroy_event', self._end_application)
        self._window.connect('delete_event', self._end_application)

        # info bar and main window vbox
        self._master_vbox = gtk.VBox(spacing=2)

        # mainbox (edit box connection by UIManager)
        self._main_box = gtk.VBox()
        self._master_vbox.add(self._main_box)

        # infobar (edit bar by InfoBar)
        self._info_frame = gtk.Frame()
        self._info_frame.set_size_request(width=0, height=30)
        self._info_frame.set_shadow_type(gtk.SHADOW_IN)
        self._master_vbox.pack_start(
            self._info_frame,
            False, False, 0
        )

        # TODO: search diff pack_start / add

        self._info_bar = InfoBar(
            self._info_frame,
            psc,
            logger
        )
        self._ui_manager = UIManager(
            self._main_box,
            psc,
            logger
        )

        self._window.add(self._master_vbox)

    def _end_application(self, window, event, data=None):
        gtk.main_quit()

        threads = threading.enumerate()
        main_thread = threading.currentThread()
        for thread in threads:
            if thread is main_thread:
                continue
            if thread.isAlive():
                thread.join(0.01)

        return False

    def main(self):
        self._window.show_all()
        gtk.main()

class FailedToReadSerialNumber(Exception):
    pass

class PhotostandConfig(object):
    _CONFIG_FILENAME    = '/etc/photostand.ini'

    def __init__(self):
        self._server_base_uri = None
        self._server_timeout = None
        self._cpu_serial = None

    def get_sensor_is_active(self):
        return self._sensor_is_active

    def get_sensor_daemon_socket_file_name(self):
        return self._sensor_daemon_socket_file_name

    def get_server_uri_base(self):
        return self._server_base_uri

    def get_server_timeout(self):
        return self._server_timeout

    def get_cpu_serial(self):
        return self._cpu_serial

    def get_password(self):
        return self._password

    def get_default_font(self):
        return self._default_font

    def get_json_reload_interval(self):
        return self._json_reload_interval

    def get_download_selfy_image_file_name(self):
        return self._download_selfy_image_file_name

    def get_download_record_voice_file_name(self):
        return self._download_record_voice_file_name

    def get_capture_selfy_image_file_name(self):
        return self._capture_selfy_image_file_name

    def get_capture_record_voice_file_name(self):
        return self._capture_record_voice_file_name

    def _read_cpuinfo(self):
        with open('/proc/cpuinfo', 'r') as f:
            for line in f:
                if line[0:6] == 'Serial':
                    self._cpu_serial = line[10:26]
                    return

        raise FailedToReadSerialNumber

    def _read_ini(self):
        # MEMO: if file not exists, ConfigParser will return [].
        if os.path.isfile(self._CONFIG_FILENAME) is False:
            raise FileNotFoundError(
                errno.ENOENT,
                os.strerror(errno.ENOENT),
                self._CONFIG_FILENAME
            )

        config = ConfigParser.RawConfigParser()

        config.read(self._CONFIG_FILENAME)

        self._server_base_uri = '{}://{}:{}{}'.format(
            config.get('server', 'protocol'),
            config.get('server', 'hostname'),
            config.get('server', 'port'),
            config.get('server', 'prefix'),
        )
        self._password = config.get('server', 'password')
        self._server_timeout = config.getint('server', 'timeout')
        self._json_reload_interval = config.getint(
            'server', 'json_reload_interval'
        )

        self._default_font = config.get('ui', 'font')

        self._sensor_is_active = config.getboolean(
            'infobar',
            'sensor_is_active'
        )

        self._sensor_daemon_socket_file_name = config.get(
            'infobar',
            'socket_file_name'
        )

        self._download_record_voice_file_name = config.get(
            'download',
            'record_voice_file_name'
        )

        self._download_selfy_image_file_name = config.get(
            'download',
            'selfy_image_file_name'
        )

        self._capture_record_voice_file_name = config.get(
            'capture',
            'record_voice_file_name'
        )

        self._capture_selfy_image_file_name = config.get(
            'capture',
            'selfy_image_file_name'
        )

        self._use_dummy_cpu_serial = config.getboolean(
            'debug',
            'use_dummy_cpu_serial'
        )

        if self._use_dummy_cpu_serial:
            self._cpu_serial = config.get('debug', 'dummy_cpu_serial')

    def read(self):
        """
        Get photostand config by ini file

        Raises
        ----
        FailedToReadSerialNumber
        FileNotFoundError
            file not found

        ConfigParser.ParsingError
        ConfigParser.MissingSectionHeaderError
            syntax error

        ConfigParser.NoSectionError
        ConfigParser.NoOptionError
            undefined critical data

        ValueError
        """

        self._read_ini()

        if not self._use_dummy_cpu_serial:
            self._read_cpuinfo()


class ServerConnection(object):
    def __init__(self, photostand_config, logger):
        self._logger = logger

        self._baseData = {
            'password':
                photostand_config.get_password(),
            'cpuSerialNumber':
                photostand_config.get_cpu_serial(),
        }

        self._psc = photostand_config

        self._csv_like_int_array_regex = re.compile(r'\A(\d+,)?\d+\Z')

        self._resentry_record_voices = None
        self._resentry_record_voices_updated = False
        self._latest_selfy_image = None
        self._latest_selfy_image_for_compare = None
        self._associated_photostands = None
        self._associated_photostands_updated = False

        self._update_objects(absolute=True)

        update_objects_in_background = threading.Thread(
            target=self._update_objects_in_background
        )
        update_objects_in_background.setDaemon(True)
        update_objects_in_background.start()

    def _update_objects_in_background(self):
        while True:
            time.sleep(self._psc.get_json_reload_interval() / 1000)
            self._update_objects()

    def _update_objects(self, absolute=False):
        while True:
            ret = self._update_resentry_record_voices_object()

            if (not absolute) or ret:
                break

            self._logger.warn('retrying...')
            time.sleep(3)

        while True:
            ret = self._update_last_selfy_image_object()

            if (not absolute) or ret:
                break

            self._logger.warn('retrying...')
            time.sleep(3)

        while True:
            ret = self._update_associated_photostands_array()

            if (not absolute) or ret:
                break

            self._logger.warn('retrying...')
            time.sleep(3)

        return True

    def _get_from_server(self, uri, expected_mime_type=''):
        self._logger.debug('Try to get by server: ' + uri)

        content = ''

        try:
            r = requests.post(
                self._psc.get_server_uri_base() + uri,
                timeout = self._psc.get_server_timeout(),
            )

            content = r.content

            r.raise_for_status()

            if r.headers.keys().count('content-type') is not 0:
                if expected_mime_type not in r.headers['content-type']:
                    self._logger.error(
                        'UnknownMimeType: ' + r.headers['content-type']
                    )
                    return False

            return content

        except requests.exceptions.HTTPError as errh:
            self._logger.error('HTTP Error: {}\n'.format(r.status_code))
            self._logger.error('ServerResponse is: ' + content)
            return False

        except requests.exceptions.ConnectionError as errc:
            self._logger.exception('Error connecting: %s', errc)
            return False

        except requests.exceptions.Timeout as errt:
            self._logger.error(
                'Timeout Error ({} seconds)'.format(
                    self._psc.get_server_timeout()
                )
            )
            return False

        except requests.exceptions.RequestException as err:
            self._logger.exception('OOps: Something Else: %s', err)
            return False

    def _post_to_server(
        self,
        uri,
        expected_mime_type='',
        additional_parameter={},
        additional_file={}
    ):
        self._logger.debug('Try to send/get data to/from server: ' + uri)

        content = ''

        try:
            r = requests.post(
                self._psc.get_server_uri_base() + uri,

                timeout =
                    self._psc.get_server_timeout(),
                data =
                    dict(
                        self._baseData.items() + additional_parameter.items()
                    ),
                files =
                    additional_file,
            )

            content = r.content

            r.raise_for_status()

            if expected_mime_type not in r.headers['content-type']:
                self._logger.error(
                    'UnknownMimeType: ' + r.headers['content-type']
                )
                return False

            return content

        except requests.exceptions.HTTPError as errh:
            self._logger.error('HTTP Error: {}\n'.format(r.status_code))
            self._logger.error('ServerResponse is: ' + content)
            return False

        except requests.exceptions.ConnectionError as errc:
            self._logger.exception('Error connecting: %s', errc)
            return False

        except requests.exceptions.Timeout as errt:
            self._logger.error(
                'Timeout Error ({} seconds)'.format(
                    self._psc.get_server_timeout()
                )
            )
            return False

        except requests.exceptions.RequestException as err:
            self._logger.exception('OOps: Something Else: %s', err)
            return False

    def _get_csv_like_int_array_from_server(
        self,
        uri,
        additional_parameter={}
    ):
        response = self._post_to_server(
            uri,
            additional_parameter    = additional_parameter,
            expected_mime_type      = 'text/plain'
        )

        if response is False:
            return False

        if response is '':
            return []

        if self._csv_like_int_array_regex.match(response) is False:
            self._logger.info('Invalid response:' + response)
            return False

        intArray = []
        for strId in response.split(','):
            intArray.append(int(strId))

        return intArray

    def _get_json_object_from_server(self, uri, additional_parameter={}):
        response = self._post_to_server(
            uri,
            additional_parameter    = additional_parameter,
            expected_mime_type      = 'application/json',
        )

        if response is False:
            return False

        dec_obj = None

        try:
            dec_obj = json.loads(response)

        except (json.deoder.JSONDecodeError) as e:
            self._logger.error('Invalid JSON: ' + response)
            return False

        return dec_obj

    def upload_record_voice(self, to_photostand_ids_array):
        self._logger.info('Try to upload record voice')

        additional_file = None
        try:
            additional_file = {
                'recordVoice': (
                    '_',
                    open(self._psc.get_capture_record_voice_file_name(), 'rb'),
                    'audio/aac'
                )
            }

        except OSError as e:
            self._logger.exception('File open error: %s', e)
            return False

        additional_parameter = {
            'toPhotostandIdsArray': ','.join(
                map(str, to_photostand_ids_array)
            )
        }

        self._post_to_server(
            '/api/upload_record_voice.php',
            expected_mime_type='',
            additional_parameter=additional_parameter,
            additional_file=additional_file
        )

        os.unlink(self._psc.get_capture_record_voice_file_name())

    def upload_selfy_image(self, to_photostand_ids_array):
        self._logger.info('Try to upload selfy image')

        additional_file = None
        try:
            additional_file = {
                'selfyImage': (
                    '_',
                    open(self._psc.get_capture_selfy_image_file_name(), 'rb'),
                    'image/jpeg'
                )
            }

        except OSError as e:
            self._logger.exception('File open error: %s', e)
            return False

        additional_parameter = {
            'toPhotostandIdsArray': ','.join(
                map(str, to_photostand_ids_array)
            )
        }

        self._post_to_server(
            '/api/upload_selfy_image.php',
            expected_mime_type='',
            additional_parameter=additional_parameter,
            additional_file=additional_file
        )

        os.unlink(self._psc.get_capture_selfy_image_file_name())

    def _update_resentry_record_voices_object(self):
        self._logger.info('Try to get record voices json.')

        new_json_object = self._get_json_object_from_server(
            '/api/get_resentry_record_voices.php',
            {'limit' : 100}
        )

        if new_json_object is False:
            return False

        self._resentry_record_voices = new_json_object

        return True

    def get_resentry_record_voices_object(self):
        return self._resentry_record_voices

    def _wget(self, uri, file_name, expected_mime_type):
        self._logger.info(
            'Try to download record voice/selfy image.'
        )

        binary = self._get_from_server(uri, expected_mime_type)

        if binary is False:
            return False

        try:
            with open(file_name, 'wb') as f:
                f.write(binary)

        except OSError as e:
            self._logger.exception('File open error: %s', e)
            return False

        return True

    def _update_last_selfy_image_object(self):
        self._logger.info('Try to get selfy image json.')

        new_json_object = self._get_json_object_from_server(
            '/api/get_last_selfy_image.php'
        )

        if new_json_object is False:
            return False

        updated = not (self._latest_selfy_image_for_compare == new_json_object)

        if not updated:
            return True

        self._latest_selfy_image_for_compare = copy.deepcopy(new_json_object)

        if len(new_json_object) is not 0:
            for photostand_id, image_info in new_json_object.items():
                if image_info['status'] is 0:
                    continue

                self._logger.info('Try to get selfy image. {}'.format(image_info['uri']))
                ret = self._wget(
                    image_info['uri'],
                    self._psc.get_download_selfy_image_file_name(),
                    'image/jpeg'
                )

                if ret is False:
                    image_info['status'] = 2
                    continue

                new_json_object[photostand_id]['pixbuf'] = \
                    gtk.gdk.pixbuf_new_from_file_at_size(
                        self._psc.get_download_selfy_image_file_name(),
                        584, 438
                    )

                os.unlink(self._psc.get_download_selfy_image_file_name())

        self._latest_selfy_image = new_json_object

        return True

    def download_aac(self, uri):
        ret = self._wget(
            uri,
            self._psc.get_download_record_voice_file_name(),
            ''
        )

        if ret is False:
            return False

        return True

    def get_last_selfy_image_object(self):
        return self._latest_selfy_image

    def _update_associated_photostands_array(self):
        self._logger.info(
            'Try to get associated photostands csv like int array.'
        )

        new_csv_array = self._get_csv_like_int_array_from_server(
            '/api/get_associated_photostands.php',
        )

        if new_csv_array is False:
            return False

        self._associated_photostands_updated = \
            self._associated_photostands_updated or \
            (new_csv_array != self._associated_photostands)

        self._associated_photostands = new_csv_array

        return True

    def get_associated_photostands_array(self):
        updated = self._associated_photostands_updated

        self._associated_photostands_updated = False

        return self._associated_photostands


FileNotFoundError = IOError

def main():
    logger = logging.getLogger(__name__)
    handler = logging.StreamHandler()
    handler.setFormatter(
        logging.Formatter("[%(asctime)s] [%(threadName)s] %(message)s")
    )
    handler.setLevel(logging.DEBUG)
    logger.setLevel(logging.DEBUG)
    logger.addHandler(handler)
    logger.propagate = False

    logger.info('Initialize Photostand Config')

    psc = PhotostandConfig()

    try:
        psc.read()

    except (FailedToReadSerialNumber):
        logger.critical('Failed to read cpu serial number.')
        sys.exit(1)

    except (
            FileNotFoundError,
            ConfigParser.ParsingError,
            ConfigParser.MissingSectionHeaderError,
            ConfigParser.NoSectionError,
            ConfigParser.NoOptionError,
            ValueError
        ):

        logger.critical(
            'Failed to read photostand config.\n' + traceback.format_exc()
        )

        sys.exit(1)

    logger.info('URI Base:   ' + psc.get_server_uri_base())
    logger.info('CPU Serial: ' + psc.get_cpu_serial())

    # init multithreading
    gobject.threads_init()
    gtk.gdk.threads_init()

    # initializing MainWindow
    main_window = MainWindow(psc, logger)

    # MainWindow loop (gtk)
    main_window.main()

if __name__ == '__main__':
    main()

