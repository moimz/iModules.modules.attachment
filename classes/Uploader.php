<?php
/**
 * 이 파일은 아이모듈 첨부파일모듈의 일부입니다. (https://www.imodules.io)
 *
 * 업로더 클래스를 정의한다.
 *
 * @file /modules/attachment/classes/Uploader.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 2. 14.
 */
namespace modules\attachment;
class Uploader
{
    /**
     * @var string $_id 업로더 고유값
     */
    private string $_id;

    /**
     * @var string $_name 입력폼이름
     */
    private string $_name = 'content';

    /**
     * @var object $_template 템플릿설정
     */
    private ?object $_template = null;

    /**
     * 기존에 첨부된 첨부파일 고유값을 지정한다.
     *
     * @param string[] $attachment_ids 첨부파일 고유값
     * @return \modules\attachment\Uploader $this
     */
    private array $_values = [];

    /**
     * @var bool $_render 첨부파일 모듈객체에 의해 자동으로 업로더를 랜더링할지 여부
     */
    private bool $_render = true;

    /**
     * 업로더 클래스를 생성한다.
     *
     * @param ?string $id 업로더 고유값 (NULL 인 경우 신규로 생성하고, 값이 존재하는 경우 기존에 첨부된 파일을 가져온다.)
     */
    public function __construct(?string $id = null)
    {
        $this->_id = $id ?? \UUID::v4();
        $this->_name = $this->_id;
    }

    /**
     * 업로더 고유값을 가져온다.
     *
     * @return string $id
     */
    public function getId(): string
    {
        return $this->_id;
    }

    /**
     * 업로더 입력폼의 이름을 가져온다.
     *
     * @return string $name 입력폼이름
     */
    public function getName(): string
    {
        return $this->_name;
    }

    /**
     * 업로더 입력폼의 이름을 설정한다.
     *
     * @param string $name 입력폼이름
     * @return \modules\attachment\Uploader $this
     */
    public function setName(string $name): \modules\attachment\Uploader
    {
        $this->_name = $name;
        return $this;
    }

    /**
     * 모듈의 컨텍스트 템플릿을 설정한다.
     *
     * @param object $template 템플릿설정
     */
    public function setTemplate(object $template): \modules\attachment\Uploader
    {
        $this->_template = $template;
        return $this;
    }

    /**
     * 모듈의 컨텍스트 템플릿을 가져온다.
     *
     * @param ?object $template 템플릿설정
     * @return \Template $template
     */
    public function getTemplate(): \Template
    {
        /**
         * @var \modules\attachment\Attachment $mAttachment
         */
        $mAttachment = \Modules::get('attachment');
        if (isset($this->_template) == true && $this->_template !== null) {
            return new \Template($mAttachment, $this->_template);
        } else {
            return new \Template($mAttachment, $mAttachment->getConfigs('template'));
        }
    }

    /**
     * 기존에 첨부된 첨부파일 고유값을 지정한다.
     *
     * @param string[] $attachment_ids 첨부파일 고유값
     * @return \modules\attachment\Uploader $this
     */
    public function setValue(array $values = []): \modules\attachment\Uploader
    {
        $this->_values = $values;
        return $this;
    }

    /**
     * 첨부파일 모듈객체에 의해 자동으로 업로더를 랜더링할지 여부를 설정한다.
     *
     * @param bool $render 첨부파일 모듈객체에 의해 자동으로 업로더를 랜더링할지 여부
     * @return \modules\attachment\Uploader $this
     */
    public function setRender(bool $render): \modules\attachment\Uploader
    {
        $this->_render = $render;
        return $this;
    }

    /**
     * 위지윅에디터 레이아웃을 가져온다.
     *
     * @return string $html 위지윅에디터 HTML
     */
    public function getLayout(): string
    {
        $template = $this->getTemplate();

        $input = \Html::element('input', [
            'type' => 'hidden',
            'data-role' => 'uploader',
            'data-id' => $this->_id,
            'name' => $this->_name,
            'value' => \Format::string(json_encode($this->_values), 'input'),
        ]);

        return \Html::element(
            'div',
            [
                'data-role' => 'module',
                'data-module' => 'attachment',
                'data-template' => $template->getName(),
            ],
            \Html::element(
                'div',
                [
                    'data-role' => 'uploader',
                    'data-id' => $this->_id,
                    'data-name' => $this->_name,
                    'data-render' => $this->_render == true ? 'true' : 'false',
                ],
                $template->getContext('uploader', $input)
            )
        );
    }
}
