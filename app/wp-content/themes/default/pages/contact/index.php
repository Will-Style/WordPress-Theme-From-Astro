<?php get_header();?>

<main id="content" class="lower contact">
        <div class="container">
            <header class="lower__header">
                <h1 class="lower__header__title">
                    <div class="lower__header__title-ja">お問い合わせ</div>
                    <div class="lower__header__title-en">Contact</div>
                </h1>
            </header>
        </div>
        <div id="contact-form" data-not-top>
            <div class="contact__wrapper">
                <div class="container">
                    <div class="contact__content">
                        <div class="grid">
                            <div class="lg:col-8 lg:start-2">
                                
                                <div class="form__wrapper py-60">
                                    
                                    <form class="form" method="post" action="?sendmail=contact" data-form>
                                        <input type="hidden" name="recaptcha_value" value="">
                                       
                                        <!--  入力画面 -->
                                        <div v-show="!isConfirm">
                                            <p>
                                                必要事項をご入力の上、確認ボタンを押してください。
                                            </p>
                                            <div class="form__row mt-20">
                                                <label for="form__type" class="form__label form__require">お問い合わせの種類</label>
                                                <div class="form__content form__vertical">
                                                    <label class="form__check-label"><input name="contact_type" type="radio" value="ご相談" :checked="type==1" @change="notChecked">ご相談</label>
                                                    <label class="form__check-label"><input name="contact_type" type="radio" value="資料請求" :checked="type==2" @change="notChecked">資料請求</label>
                                                </div>
                                                <div class="form__invalid" v-if="errors.contact_type">お問い合わせの種類を選択してください。</div>
                                            </div>
                                          
                                            <div class="form__row">
                                                <label class="form__label form__require">お名前</label>

                                                <div class="form__content">
                                                    <input type="text" name="name" placeholder="お名前" @blur="notEmpty">
                                                    <div class="form__invalid" v-if="errors.name">お名前を入力してください。</div>
                                                </div>

                                            </div>
                                            <div class="form__row">
                                                <label class="form__label form__require">フリガナ</label>
                                                <div class="form__content">
                                                    <input type="text" name="kana" placeholder="フリガナ" @blur="notEmpty">
                                                    <div class="form__invalid" v-if="errors.kana">フリガナを入力してください。</div>
                                                </div>
                                            </div>
                                            
                                            <div class="form__row">
                                                <label class="form__label form__option">郵便番号</label>
                                                <div class="form__content">
                                                    <input type="text" name="zip" placeholder="郵便番号" data-form-zip="address" data-form-alphanum>
                                                </div>
                                            </div>
                                            <div class="form__row">
                                                <label class="form__label form__require">住所</label>
                                                <div class="form__content">
                                                    <input type="text" name="address" placeholder="住所" @blur="notEmpty">
                                                    <div class="form__invalid" v-if="errors.address">住所を入力してください。</div>
                                                </div>
                                            </div>

                                            <div class="form__row">
                                                <label class="form__label form__require">連絡先電話番号</label>
                                                <div class="form__content">
                                                    <input type="text" name="phone" placeholder="連絡先電話番号" @blur="notEmpty" data-form-alphanum>
                                                    <div class="form__invalid" v-if="errors.phone">連絡先電話番号を入力してください。</div>
                                                </div>
                                            </div>
                                            <div class="form__row"> 
                                                <label class="form__label form__require">メールアドレス</label>
                                                <div class="form__content">
                                                    <input type="email" name="email" placeholder="メールアドレス" @blur="notEmail">
                                                    <div class="form__invalid" v-if="errors.email == 'empty'">メールアドレスを入力してください。</div>
                                                    <div class="form__invalid" v-if="errors.email == 'not_email'">メールアドレスの形式で入力してください。</div>
                                                </div>
                                            </div>
                                        
                                            <div class="form__row">
                                                <label class="form__label form__require">お問い合わせ内容</label>
                                                <div class="form__content">
                                                    <textarea name="message" rows="10" placeholder="お問い合わせ内容を入力してください" @blur="notEmpty"></textarea>
                                                    <div class="form__invalid" v-if="errors.message">お問い合わせ内容を入力してください。</div>
                                                </div>
                                            </div>
                                            <div class="form__confirm__hidden gap-y-10 form__row">
                                                <div class="form__content">
                                                    <p class="form__pp text-center"><a href="../pp/" class="text-underline" data-window-open target="_blank" rel="noopener noreferrer">個人情報の取り扱い</a>についてご同意の上、送信してください。</p>
                                                </div>
                                            </div>
                                            <div class="text-center pt-40">
                                                <button type="submit" class="form__submit" data-form-submit v-bind:disabled="!verified" v-bind:class="{':prosessing':prosessing}" @click="confirm">送信内容を確認する</button>
                                            </div>
                    
                                            <p class="form__confirm__hidden form__pp text-center pt-40 pb-60">このサイトはreCaptchaによって保護されており、<br> Googleの<a href="https://policies.google.com/privacy?hl=ja" target="_blank" rel="noopener noreferrer" class="text-underline">プライバシーポリシー</a>と<a href="https://policies.google.com/terms?hl=ja" target="_blank" rel="noopener noreferrer" class="text-underline">利用規約</a>が適用されます。</p>
                                        </div>
                                        
                                        <!--  確認画面 -->
                                        <div v-show="isConfirm">
                                            <p>
                                                以下の内容でよろしければ【メールを送信する】ボタンを押下してください。
                                            </p>
                                            <div class="form__row mt-20">
                                                <label for="form__type" class="form__label form__require">お問い合わせの種類</label>
                                                <div class="form__content form__vertical">
                                                    <div v-text="data.contact_type">
                                                </div>
                                            </div>
                                            <div class="form__row">
                                                <label class="form__label form__require">お名前</label>

                                                <div class="form__content">
                                                    <div v-text="data.name"></div>
                                                </div>

                                            </div>
                                            <div class="form__row">
                                                <label class="form__label form__require">フリガナ</label>
                                                <div class="form__content">
                                                    <div v-text="data.kana"></div>
                                                </div>
                                            </div>
                                            <div class="form__row">
                                                <label class="form__label form__option">郵便番号</label>
                                                <div class="form__content">
                                                    <div v-text="data.zip"></div>
                                                </div>
                                            </div>
                                            <div class="form__row">
                                                <label class="form__label form__require">住所</label>
                                                <div class="form__content">
                                                    <div v-text="data.address"></div>
                                                </div>
                                            </div>

                                            <div class="form__row">
                                                <label class="form__label form__require">連絡先電話番号</label>
                                                <div class="form__content">
                                                    <div v-text="data.phone"></div>
                                                </div>
                                            </div>
                                            <div class="form__row"> 
                                                <label class="form__label form__require">メールアドレス</label>
                                                <div class="form__content">
                                                    <div v-text="data.email"></div>
                                                </div>
                                            </div>
                                        
                                            <div class="form__row">
                                                <label class="form__label form__require">お問い合わせ内容</label>
                                                <div class="form__content">
                                                    <div v-text="data.message"></div>
                                                </div>
                                            </div>
                                           
                                            <div class="text-center pt-40">
                                                <button type="submit" class="form__submit" data-form-submit v-bind:disabled="!verified" v-bind:class="{':prosessing':prosessing}" @click="submit">メールを送信する</button>
                                            </div>
                                            <div class="text-center pt-20">
                                                <button type="button" class="form__return" data-form-return v-bind:class="{':prosessing':prosessing}" @click="returnInput">入力画面に戻る</button>
                                            </div>
                                            <p class="form__confirm__hidden form__pp text-center pt-40 pb-60">このサイトはreCaptchaによって保護されており、<br> Googleの<a href="https://policies.google.com/privacy?hl=ja" target="_blank" rel="noopener noreferrer" class="text-underline">プライバシーポリシー</a>と<a href="https://policies.google.com/terms?hl=ja" target="_blank" rel="noopener noreferrer" class="text-underline">利用規約</a>が適用されます。</p>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

<?php get_footer();
